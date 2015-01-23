package main

import (
    "fmt"
    "os"
    "strings"
    "encoding/json"
    "time"
    "bytes"
    "io"
    "bufio"
    "log"
    "path/filepath"
    "strconv"
)

func ContainsListAny(str string, a []string) bool {
    for _, v := range a {
        if strings.Contains(str, v) {
            return true;
        }
    }
    return false;
}

type Pair struct {
    root_server string
    root_client string
    ignore [] string
}
type Config struct {
    url string
    pairs []Pair
}
func ReadJson(path string, c *map[string]interface{}) error {
    file, err := os.Open(path)
    if err != nil {
        return err
    }
    defer file.Close()

    dec := json.NewDecoder(bufio.NewReader(file))
    for {
        if err := dec.Decode(c); err == io.EOF {
            break
        } else if err != nil {
            log.Fatal(err)
        }
    }

    return nil
}

func GetConfig() (map[string]interface{}, error) {
    var c map[string]interface{}
    err := ReadJson("config.default.json", &c)
    if err != nil {
        return c, err
    }
    err = ReadJson("config.user.json", &c)
    return c, nil
}
func readTime(path string, t *map[string]time.Time) (error) {
    file, err := os.Open(path)
    if err != nil {
        return nil
    }
    defer file.Close()

    dec := json.NewDecoder(bufio.NewReader(file))
    for {
        if err := dec.Decode(&t); err == io.EOF {
            break
        } else if err != nil {
            log.Fatal(err)
        }
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
    t := time.Now()
    var d = map[string]time.Time{}
    config, err := GetConfig()
    if err != nil {
        log.Fatal(err)
    }
    for {
        pairs := config["pairs"].([]interface{})
        for i, pair := range pairs {
            p := pair.(map[string]interface{})
            tf := "ModTimeTable." + strconv.Itoa(i)
            a := [...]string{".git", ".idea", tf}
            err := readTime(tf, &d)
            if err != nil {
                log.Fatal(err)
            }
            root := p["root_client"].(string)
            log.Println("looking", root)
            filepath.Walk(root, func (path string, info os.FileInfo, err error) error {
                if err != nil {
                    log.Println(err)
                    return nil
                }
                fmt.Println(info)
                idir := info.IsDir()
                if !idir && !ContainsListAny(path, a[:]) {
                    if d[path] != info.ModTime() {
                        fmt.Println("upload", path)
                        d[path] = info.ModTime()
                        dest := p["root_server"].(string) + path[len(root):]
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
}
