package main

import "os"
import "fmt"
import "log"
import "flag"
import "time"
import "net"
import "bufio"
import "strings"
import "strconv"
import "io/ioutil"
import (
	"database/sql"
	_ "github.com/mattn/go-sqlite3"
)

func IsIgnore(name string, ign []string) bool {
	for _, i := range ign {
		if name == i {
			return true
		}
	}
	return false
}

type Uploader struct {
	hostport string
	dest     string
	root     string
	ignore   []string
	ftt      *FileTimeTable
	remember bool

	conn *net.TCPConn
	rd   *Reader
	wt   *Writer
}

func NewUploader(hostport string, dest string, root string, ignore []string, ftt *FileTimeTable, remember bool) *Uploader {
	r := new(Uploader)
	r.reset(hostport, dest, root, ignore, ftt, remember)
	return r
}
func (u *Uploader) Cwd(path string) *Uploader {
	dest := u.dest + "/" + path
	root := u.root + "/" + path
	return NewUploader(u.hostport, dest, root, u.ignore, u.ftt, u.remember)
}
func (u *Uploader) reset(hostport string, dest string, root string, ignore []string, ftt *FileTimeTable, remember bool) {
	*u = Uploader{
		hostport: hostport,
		dest:     dest,
		root:     root,
		ignore:   ignore,
		ftt:      ftt,
		remember: remember,
	}
}
func (u *Uploader) Upload() {
	stat, err := os.Stat(u.root)
	handle_error(err)
	if !stat.IsDir() {
		log.Fatal(u.root + " is not dir")
	}
	file, err := os.Open(u.root)
	handle_error(err)
	fis, err := file.Readdir(0)
	handle_error(err)
	for _, fi := range fis {
		// fmt.Printf("process %s\n", fi.Name())
		if fi.IsDir() {
			nu := u.Cwd(fi.Name())
			go nu.Upload()
		} else {
			fn := u.root + "/" + fi.Name()
			if u.remember {
				t := fi.ModTime()
				if u.ftt.IsBefore(fn, t) {
					fmt.Printf("upload %s\n", fi.Name())
					u.upload_file(fn, u.dest+"/"+fi.Name())
					defer u.Close()
				}
			} else {
				u.upload_file(fn, u.dest+"/"+fi.Name())
				defer u.Close()
			}
		}
	}
}
func (u *Uploader) Close() {
	header := make(map[string]interface{})
	header["cc"] = true
	fmt.Printf("send close %+v\n", header)
	u.wt.WriteHeader(header)
	u.conn.Close()
}
func (u *Uploader) upload_file(src string, dest string) {
	fmt.Printf("%s ==> %s\n", src, dest)

	u.ensure_dial()

	b, err := ioutil.ReadFile(src)
	fmt.Printf("read file %s:\n%s\n", src, string(b))
	handle_error(err)
	header := make(map[string]interface{})
	header["fp"] = dest
	header["cl"] = len(b)
	fmt.Printf("send %+v\n", header)
	u.wt.WriteHeader(header)
	n := u.wt.WriteBody(b)
	fmt.Printf("write %d\n", n)

	resp := u.rd.ReadHeader()
	coderaw, ok := resp["code"] // code raw
	code := int(coderaw.(float64))
	if ok && code == 0 {
		return
	} else {
		log.Fatal("code not 0, but" + strconv.Itoa(code))
	}
}
func (u *Uploader) ensure_dial() {
	if u.conn != nil {
		return
	}
	conn, err := net.Dial("tcp", u.hostport)
	handle_error(err)
	u.conn = conn.(*net.TCPConn)
	u.rd = NewReader(bufio.NewReader(conn))
	u.wt = NewWriter(bufio.NewWriter(conn))
}

type FileTimeTable struct {
	db *sql.DB
}

func NewFileTimeTable() *FileTimeTable {
	db_file := "./file_time_data.db"
	first_time := false
	if _, err := os.Stat(db_file); os.IsNotExist(err) {
		first_time = true
	}
	db, err := sql.Open("sqlite3", db_file)
	if err != nil {
		log.Fatal(err)
	}
	if first_time {
		sqlStmt := `
        create table file_time_data (
            name text not null primary key,
            t TimeStamp);
        `
		_, err = db.Exec(sqlStmt)
		handle_error(err)
	}
	ftt := new(FileTimeTable)
	*ftt = FileTimeTable{
		db: db,
	}
	return ftt
}

func (ftt *FileTimeTable) IsBefore(fn string, t time.Time) (r bool) {
	tx, err := ftt.db.Begin()
	if err != nil {
		log.Fatal(err)
	}
	r = false
	var oldt time.Time // old time
	s := "SELECT t from file_time_data WHERE name=? limit 1"
	fmt.Println(s)
	err = tx.QueryRow(s, fn).Scan(&oldt)
	fmt.Println(oldt)
	var stmt *sql.Stmt
	switch {
	case err == sql.ErrNoRows:
		fmt.Println("insert")
		stmt, err = tx.Prepare("INSERT into file_time_data(t, name) values(?, ?)")
		if err != nil {
			log.Fatal(err)
		}
		r = true
	case err != nil:
		log.Fatal(err)
	default:
		r = oldt.Before(t)
		fmt.Println("maybe upload")
		if r {
			fmt.Println("upload")
			stmt, err = tx.Prepare("UPDATE file_time_data set t=? where name=?")
			if err != nil {
				log.Fatal(err)
			}
		}
	}
	if stmt != nil {
		defer stmt.Close()
		_, err = stmt.Exec(t, fn)
		handle_error(err)
		fmt.Println("Commit")
		tx.Commit()
	} else {
		tx.Rollback()
	}
	return r
}
func (ftt *FileTimeTable) ShowAll() {
	s := "SELECT t,name from file_time_data"
	rows, err := ftt.db.Query(s)
	handle_error(err)
	defer rows.Close()
	for rows.Next() {
		var t time.Time
		var name string
		rows.Scan(&t, &name)
		fmt.Println(t, name)
	}
}
func (ftt *FileTimeTable) Close() {
	ftt.db.Close()
}

func main() {
	log.SetFlags(log.LstdFlags | log.Lshortfile)

	var url_ = flag.String("url", "", "server script url")
	var dest = flag.String("dest", ".", "a dir where to put files")
	var root = flag.String("root", ".", "local dir")
	var ignore = flag.String("ignore", ".git;modify.json", "file or dir you want to ignore, separated by ';'")
	var remember = flag.Bool("m", false, "remember what have transfered, so next time only changed files will be transfered")
	var watch = flag.Bool("w", false, "see if file changes every 0.5 s, must used with -m")
	var show_data = flag.Bool("sd", false, "see debug data info")
	flag.Parse()

	ftt := NewFileTimeTable()
	defer ftt.Close()
	if *show_data {
		ftt.ShowAll()
		return
	}

	if len(*url_) == 0 {
		fmt.Printf("upload file to server\n")
		flag.PrintDefaults()
		os.Exit(1)
	}
	fmt.Printf("from %s to %s:%s\n\n", *root, *url_, *dest)
	ign := strings.Split(*ignore, ";")
	fmt.Printf("ignore %v\n\n", ign)

	for {
		up := NewUploader(*url_, *dest, *root, ign, ftt, *remember)
		up.Upload()
		if *watch {
			fmt.Printf("Sleep 0.5 s\r")
			time.Sleep(500 * time.Millisecond)
		} else {
			break
		}
	}
}

func handle_error(err error) {
	if err != nil {
		log.Fatal(err)
	}
}
