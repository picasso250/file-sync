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
    file, err := os.Create(path)
    if err != nil {
        return err
    }
    defer file.Close()

    w := bufio.NewWriter(file)
    fmt.Fprint(w, s)
    return w.Flush()
}
func upload(path string, dest string) {
    fmt.Println("from", path, "to", dest)
}
func main() {
    tf := "ModTimeTable"
    a := [...]string{".git", ".idea", tf}
    t := time.Now()
    var d = map[string]time.Time{}
    for {
        err := readTime(tf, &d)
        if err != nil {
            log.Fatal(err)
        }
        root := "D:\\file-sync"
        filepath.Walk(root, func (path string, info os.FileInfo, err error) error {
            if !info.IsDir() && !ContainsListAny(path, a[:]) {
                if d[path] != info.ModTime() {
                    fmt.Println("upload", path)
                    d[path] = info.ModTime()
                    dest := "/home/work" + path[len(root):]
                    upload(path, dest)
                }
            }
            return nil
        })
        
        err = writeTime(tf, d)
        if err != nil {
            log.Fatal(err)
        }
        time.Sleep(1000 * time.Millisecond)
        fmt.Print("\rsleep ", time.Now().Sub(t))
    }
}
