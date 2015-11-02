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

func UploadFile(file string, dest string, url_ string) {
  data, err := ioutil.ReadFile(file)
  if err != nil {
    log.Fatal(err)
  }
  vals := url.Values{}
  vals.Set("action", "upload_file")
  vals.Set("format", "base64")
  vals.Set("dest", dest)
  vals.Set("data", string(data[:len(data)]))
  resp, err := http.PostForm(url_, vals)
  if err != nil {
    log.Fatal(err)
  }
  defer resp.Body.Close()
  body, err := ioutil.ReadAll(resp.Body)
  if err != nil {
    log.Fatal(err)
  }
  fmt.Printf(string(body[:len(body)])+"\n")
}

func IsIgnore(name string, ign []string) bool {
  for _,i := range ign {
    if name == i {
      return true
    }
  }
  return false
}

func Upload(path string, root string, dest string, url_ string) {
  fmt.Printf("Upload %s\n", path)
  name := strings.TrimPrefix(path, root)
  UploadFile(path, dest+name, url_)
}

func LoadModify(path string) (map[string]time.Time, error) {
  modify := make(map[string]time.Time)
  data, err := ioutil.ReadFile(path)
  if err != nil {
    return nil, err
  }
  err = json.Unmarshal(data, &modify)
  return modify, err
}
func LoadModifyOpt(path string) (map[string]time.Time, error) {
  modify := make(map[string]time.Time)
  if _, err := os.Stat(path); err == nil {
    return LoadModify(path)
  }
  return modify, nil
}
func SaveModify(modify map[string]time.Time, path string) error {
  b, err := json.Marshal(modify)
  if err != nil {
    return err
  }
  err = ioutil.WriteFile(path+".tmp", b, 0644)
  if err != nil {
    return err
  }
  if _, err := os.Stat(path); err == nil {
    err = os.Remove(path)
    if err != nil {
      return err
    }
  }
  err = os.Rename(path+".tmp", path)
  return err
}

func GetModifyFileName(url_ string, dest string, root string) string {
  h := md5.New()
  io.WriteString(h, url_)
  io.WriteString(h, dest)
  io.WriteString(h, root)
  return fmt.Sprintf("%s/%x%s", os.TempDir(), h.Sum(nil), "_modify.json")
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
  mfile := GetModifyFileName(*url_, *dest, *root)
  fmt.Printf("data file %s\n", mfile)
  modify, err := LoadModifyOpt(mfile)
  if err != nil {
    log.Fatal(err)
  }
  for {
    err := filepath.Walk(*root, func (path string, info os.FileInfo, err error) error {
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
