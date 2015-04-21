package main

import "os"
import "fmt"
import "log"
import "flag"
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

func main() {
  var url_ = flag.String("url", "http://localhost/http_server.php", "server script url")
  var dest = flag.String("dest", ".", "a dir where to put files")
  var root = flag.String("root", ".", "local dir")
  fmt.Printf(*dest)
  filepath.Walk(*root, func (path string, info os.FileInfo, err error) error {
    if err != nil {
      log.Fatal(err)
    }
    if path == ".git" {
      return filepath.SkipDir
    }
    if path != "." && !info.IsDir() {
      fmt.Printf(path+"\n")
      upload(*root+"/"+path, *dest, *url_)
    }
    return nil
  })
}
