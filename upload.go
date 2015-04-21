package main

import "os"
import "fmt"
import "log"
import "flag"
import "strings"
import "net/url"
import "net/http"
import "io/ioutil"
import "path/filepath"

func upload(file string, dest string, url_ string) {
  data, err := ioutil.ReadFile(file)
  if err != nil {
    log.Fatal(err)
  }
  vals := url.Values{}
  vals.Set("action", "upload_file")
  vals.Set("file_name", (file))
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

func main() {
  var url_ = flag.String("url", "http://localhost/http_server.php", "server script url")
  var dest = flag.String("dest", ".", "a dir where to put files")
  var root = flag.String("root", ".", "local dir")
  var ignore = flag.String("ignore", ".git", "local dir")
  flag.Parse()
  fmt.Printf("from %s to %s:%s\n\n", *root, *url_, *dest)
  ign := strings.Split(*ignore, ";")
  fmt.Printf("ignore %v\n", ign)
  filepath.Walk(*root, func (path string, info os.FileInfo, err error) error {
    if err != nil {
      log.Fatal(err)
    }
    fmt.Printf("process %s\n", path)
    if IsIgnore(info.Name(), ign) {
      fmt.Printf("skip %s\n", path)
      return filepath.SkipDir
    }
    if path != "." && !info.IsDir() {
      fmt.Printf("upload %s\n", path)
      upload(path, *dest, *url_)
    }
    return nil
  })
}
