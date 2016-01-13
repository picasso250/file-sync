package main

import "os"
import "io"
import "fmt"
import "log"
import "flag"
import "time"
import "strings"
import "net/url"
import "net/http"
import "io/ioutil"
import "crypto/md5"
import "path/filepath"
import "encoding/json"
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
    dest string
    root string
    ignore []string
    ftt *FileTimeTable
}
func NewUploader(hostport string, dest string, root string, ignore []string, ftt *FileTimeTable) *Uploader {
    r := new(Uploader)
    r.reset(hostport, dest, root, ignore, ftt)
    return r
}
func (u *Uploader) Cwd(path string) *Uploader {
    dest += "/" + path
    root += "/" + path
    return NewUploader(u.hostport, dest, root, u.ignore, u.ftt)
}
func (u *Uploader) reset(hostport string, dest string, root string, ignore []string, ftt *FileTimeTable) {
    *u = Uploader {
        hostport: hostport,
        dest: dest,
        root: root,
        ignore: ignore,
    }
}
func (u *Uploader) Upload()
{
    file, err := os.Create(u.root)
    handle_error(err)
    stat, err := file.Stat()
    handle_error(err)
    if !stat.IsDir() {
        log.Fatal(u.root+" is not dir")
    }
    fis, err := file.Readdir(0)
    handle_error(err)
    for i,fi := range fis {
        if fi.IsDir() {
            nu := u.Cwd(fi.Name())
            go nu.upload()
        } else {
            t := fi.ModTime()
            if u.ftt.Get(fi.Name()).Before(t) {
                u.upload_file(fi.Name())
                defer u.conn.Close()
                fmt.Printf("upload %s\n", fi.Name())
                u.ftt.Set(fi.Name(), t)
            }
        }
    }
}
func (u *Uploader) upload_file(src string, dest string) {

    u.ensure_dial()

    b, err := ioutil.ReadFile(fn)
    handle_error(err)
    header := make(map[string]interface{})
    header["fp"] = dest
    header["cl"] = len(b)
    u.wt.WriteHeader(header)
    u.wt.WriteBody(b)

    header := u.rd.ReadHeader()
    code, ok := header["code"]
    if ok && code == 0 {
        return
    } else {
        log.Fatal("code not 0, but"+strconv.Itoa(code))
    }
}
func (u *Uploader) ensure_dial() {
    if u.conn != nil {
        return
    }
    conn, err := net.Dial("tcp", u.hostport)
    handle_error(err)
    u.conn = conn
    u.rd = hyperjson.NewReader(bufio.NewReader(conn))
    u.wt = hyperjson.NewWriter(bufio.NewWriter(conn))
}

type FileTimeTable struct {
    db *sql.DB
}

func NewFileTimeTable() *FileTimeTable {
    db, err := sql.Open("sqlite3", "./file_time_data.db")
    if err != nil {
        log.Fatal(err)
    }
    var t string
    err := db.QueryRow("show tables like ?", "file_time_data").Scan(&t)
    handle_error(err)
    switch {
    case err == sql.ErrNoRows:
        sqlStmt := `
        create table file_time_data (
            name text not null primary key,
            t text);
        `
        _, err = db.Exec(sqlStmt)
        handle_error(err)
    case err != nil:
            log.Fatal(err)
    default:
    }
    t := new(FileTimeTable)
    t = FileTimeTable {
        db: db,
    }
    return t
}

func (t *FileTimeTable) IsBefore(fn string, t time.Time) (r bool) {
    tx, err := t.db.Begin()
    if err != nil {
        log.Fatal(err)
    }
    r = false
    var oldt time.Time
    sql := "SELECT time_ from file_time_data WHERE name=? limit 1"
    err := tx.QueryRow(sql, fn).Scan(&oldt)
    var stmt sql.Stmt
    switch {
    case err == sql.ErrNoRows:
        stmt, err := tx.Prepare("insert into file_time_data(t, name) values(?, ?)")
        if err != nil {
            log.Fatal(err)
        }
        defer stmt.Close()
        r = true
    case err != nil:
            log.Fatal(err)
    default:
        r = t.Before(t)
        if r {
            stmt, err := tx.Prepare("update file_time_data set t=? where name=?")
            if err != nil {
                log.Fatal(err)
            }
            defer stmt.Close()
        }
    }
    if stmt != nil {
        _, err = stmt.Exec(t, fn)
        handle_error(err)
        tx.Commit()
    } else {
        tx.Rollback()
    }
    return r
}
func (t *FileTimeTable) Get(fn string) (t time.Time, err error) {
    err := t.db.QueryRow("SELECT time_ from file_time_data WHERE name=? limit 1", fn).Scan(&t)
    return t, err
}
func (t *FileTimeTable) Set(fn string, t time.Time) {
    tx, err := t.db.Begin()
    if err != nil {
        log.Fatal(err)
    }
    err := tx.QueryRow("SELECT time_ from file_time_data WHERE name=? limit 1", fn).Scan(&t)
    var stmt sql.Stmt
    switch {
    case err == sql.ErrNoRows:
        stmt, err := tx.Prepare("insert into file_time_data(t, name) values(?, ?)")
        if err != nil {
            log.Fatal(err)
        }
        defer stmt.Close()
    case err != nil:
            log.Fatal(err)
    default:
        stmt, err := tx.Prepare("update file_time_data set t=? where name=?")
        if err != nil {
            log.Fatal(err)
        }
        defer stmt.Close()
    }
    _, err = stmt.Exec(t, fn)
    handle_error(err)
    tx.Commit()
}
func (t *FileTimeTable) Close() {
    t.db.Close()
}

func main() {
	var url_ = flag.String("url", "", "server script url")
	var dest = flag.String("dest", ".", "a dir where to put files")
	var root = flag.String("root", ".", "local dir")
	var ignore = flag.String("ignore", ".git;modify.json", "file or dir you want to ignore, separated by ';'")
	var remember = flag.Bool("m", false, "remember what have transfered, so next time only changed files will be transfered")
	var watch = flag.Bool("w", false, "see if file changes every 0.5 s, must used with -m")
	flag.Parse()
	if len(*url_) == 0 {
		fmt.Printf("upload file to server\n")
		flag.PrintDefaults()
		os.Exit(1)
	}
	fmt.Printf("from %s to %s:%s\n\n", *root, *url_, *dest)
	ign := strings.Split(*ignore, ";")
	fmt.Printf("ignore %v\n\n", ign)

    file, error := os.Create(*root)

	for {
		err := filepath.Walk(*root, func(path string, info os.FileInfo, err error) error {
			if err != nil {
				log.Fatal(err)
			}
			if IsIgnore(info.Name(), ign) {
				fmt.Printf("skip %s\r", path)
				if info.IsDir() {
					return filepath.SkipDir
				} else {
					return nil
				}
			}
			if path != "." && !info.IsDir() {
				if *remember {
					t, ok := modify[path]
					if ok {
						if t.Before(info.ModTime()) {
							modify[path] = info.ModTime()
							Upload(path, *root, *dest, *url_)
							err = SaveModify(modify, mfile)
							if err != nil {
								log.Fatal(err)
							}
						}
					} else {
						modify[path] = info.ModTime()
						Upload(path, *root, *dest, *url_)
						err = SaveModify(modify, mfile)
						if err != nil {
							log.Fatal(err)
						}
					}
				} else {
					Upload(path, *root, *dest, *url_)
				}
			}
			return nil
		})
		if err != nil {
			log.Fatal(err)
		}
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
