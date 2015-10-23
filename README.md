file-sync
=========

将本地的（代码）文件上传到服务器上的指定目录。

用法
------

1. 将 `http_server.php` 放在服务器上。并使其web服务可用。
2. 下载`upload.exe` 下载地址为 `http://zhihu233.top:8081/upload.exe`
3. 在本地命令行执行 `upload.exe -url http://example.com/http_server.php" -root D:\code -dest "/var/www"

非Windows系统
------------

请先安装Go-lang，然后 `go build upload.go`

upload 选项
------------

```
  -url    required, server script url like "http://domamin/http_server.php"
  -dest   required, a dir where to put files like "."
  -root   required, local dir like "."
  -ignore file or dir you want to ignore, separated by ";" like ".get;.svn"
  -m      remember what have transfered, so next time only changed files will be transfered
  -w      see if file changes every 0.5 s, must used with -m
```

通过使用 -m 可以避免上传之前已经上传过的文件

通过使用 -m 和 -w 可以实时不间断的监控文件夹的变化，实现代码实时上传。

通过 -ignore ".git;.svn;.idea" 可以忽略 .git .svn .idea 这三个文件夹。
