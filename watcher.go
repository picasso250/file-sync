package main

import (
    "fmt"
    "os"
    "path/filepath"
    "strings"
    "encoding/json"
    "time"
    "bytes"
    "io"
    "bufio"
    "log"
)

func FileMTime(file string) (int64, error) {
    f, e := os.Stat(file)
    if e != nil {
        return 0, e
    }
    return f.ModTime().Unix(), nil
}

// get file size as how many bytes
func FileSize(file string) (int64, error) {
    f, e := os.Stat(file)
    if e != nil {
        return 0, e
    }
    return f.Size(), nil
}

// delete file
func Unlink(file string) error {
    return os.Remove(file)
}

// rename file name
func Rename(file string, to string) error {
    return os.Rename(file, to)
}

// put string to file
func FilePutContent(file string, content string) (int, error) {
    fs, e := os.Create(file)
    if e != nil {
        return 0, e
    }
    defer fs.Close()
    return fs.WriteString(content)
}

func ContainsListAny(str string, a []string) bool {
    for _, v := range a {
        if strings.Contains(str, v) {
            return true;
        }
    }
    return false;
}

func readTime(path string, t *map[string]time.Time) (error) {
    file, err := os.Open(path)
    if err != nil {
        return err
    }
    defer file.Close()

    dec := json.NewDecoder(bufio.NewReader(file))
    for {
        if err := dec.Decode(&t); err == io.EOF {
            break
        } else if err != nil {
            log.Fatal(err)
        }
        // fmt.Printf("%s: %s\n", m.Name, m.Text)
    }

    return nil
}

func writeTime(path string, d map[string]time.Time) error {
    j, err := json.Marshal(d)
    if err != nil {
        return err
    }
    s := bytes.NewBuffer(j).String()
    fmt.Println(s)
    file, err := os.Create(path)
    if err != nil {
        return err
    }
    defer file.Close()

    w := bufio.NewWriter(file)
    fmt.Fprint(w, s)
    return w.Flush()
}

func main() {
    a := [...]string{".git", ".idea"}
    var d = map[string]time.Time{}
    tf := "ModTimeTable"
    err := readTime(tf, &d)
    if err != nil {
        log.Fatal(err)
    }
    for {
        filepath.Walk("D:\\file-sync", func (path string, info os.FileInfo, err error) error {
            if !info.IsDir() && !ContainsListAny(path, a[:]) {
                fmt.Println(path)
                fmt.Println(info)
                d[path] = info.ModTime()
            }
            return nil
        })
        
        err = writeTime(tf, d)
        if err != nil {
            log.Fatal(err)
        }
        time.Sleep(1000 * time.Millisecond)
    }
}
