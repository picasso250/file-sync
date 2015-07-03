file-sync
=========

将本地的文本文件传到服务器上的指定目录。

用法
------

将 `http_server.php` 放在服务器上。
在本地命令行执行 `go run upload.go`

本地选项

```
  -url    required, server script url like "http://domamin/http_server.php"
  -dest   required, a dir where to put files like "."
  -root   required, local dir like "."
  -ignore file or dir you want to ignore, separated by ";" like ".get;.svn"
  -m      remember what have transfered, so next time only changed files will be transfered
  -w      see if file changes every 0.5 s, must used with -m
```
