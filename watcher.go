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
    "mime/multipart"
    "net/http"
    "io/ioutil"
)

func HasSuffixAny(str string, a []string) bool {
    for _, v := range a {
        if strings.HasSuffix(str, v) {
            return true;
        }
    }
    return false;
}
func ContainsAny(str string, a []string) bool {
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
func ReadTime(path string, t *map[string]time.Time) (error) {
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

func WriteTime(path string, d map[string]time.Time) error {
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
func Upload(url string, file string, dest string) (err error) {
    fmt.Println("from", file, "to", dest)
    // Prepare a form that you will submit to that URL.
    var b bytes.Buffer
    w := multipart.NewWriter(&b)
    // Add your file
    f, err := os.Open(file)
    if err != nil {
        return
    }
    fw, err := w.CreateFormFile("f", file)
    if err != nil {
        return
    }
    if _, err = io.Copy(fw, f); err != nil {
        return
    }
    // Add the other fields
    if fw, err = w.CreateFormField("dest"); err != nil {
        return
    }
    if _, err = fw.Write([]byte(dest)); err != nil {
        return
    }
    if fw, err = w.CreateFormField("action"); err != nil {
        return
    }
    if _, err = fw.Write([]byte("upload_file")); err != nil {
        return
    }
    // Don't forget to close the multipart writer.
    // If you don't close it, your request will be missing the terminating boundary.
    w.Close()

    // Now that you have a form, you can submit it to your handler.
    req, err := http.NewRequest("POST", url, &b)
    if err != nil {
        return
    }
    // Don't forget to set the content type, this will contain the boundary.
    req.Header.Set("Content-Type", w.FormDataContentType())

    // Submit the request
    client := &http.Client{}
    res, err := client.Do(req)
    if err != nil {
        return
    }

    // Check the response
    if res.StatusCode != http.StatusOK {
        err = fmt.Errorf("bad status: %s", res.Status)
    }
    body, err := ioutil.ReadAll(res.Body)
    if err != nil {
        return
    }
    fmt.Println(bytes.NewBuffer(body).String())
    return
}
func main() {
    t := time.Now()
    var d = map[string]time.Time{}
    config, err := GetConfig()
    if err != nil {
        log.Fatal(err)
    }
    url := config["url"].(string)
    focus := [...]string{".doc", ".xls"}
    for {
        tf := "ModTimeTable"
        err := ReadTime(tf, &d)
        if err != nil {
            log.Fatal(err)
        }
        root := "d:"
        filepath.Walk(root, func (path string, info os.FileInfo, err error) error {
            if err != nil {
                log.Println(err)
                return nil
            }
            idir := info.IsDir()
            if !idir && HasSuffixAny(path, focus[:]) {
                if d[path] != info.ModTime() {
                    d[path] = info.ModTime()
                    dest := "/home/users/wangxiaochi/test/file-sync"
                    Upload(url, path, dest)
                }
            }
            return nil
        })
        
        err = WriteTime(tf, d)
        if err != nil {
            log.Fatal(err)
        }
        time.Sleep(1000 * time.Millisecond)
        fmt.Print("\rsleep ", time.Now().Sub(t))
    }
}
