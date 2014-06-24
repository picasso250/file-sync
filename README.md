file-sync
=========

增加开发效率

将本地的文件实时（现在还未做到）的传到服务器上的指定目录。

原理
------
每隔一秒，检测本地文件的mtime，如有变化，传到服务器。服务器开端口监听。将收到的文件写入指定位置。

用法
------

在服务器上打开 server.php 。在本地打开 watch.php 

配置
-------

config.default.json 使用json格式

```json
{
    "host": "127.0.0.1", // 监听的IP
    "port": 5666,        // 端口
    "root_server": "./server", // 服务器的根目录
    "root_client": "./client", // 客户端的监视的根目录
    "ignore": [".git", ".svn", ".idea"] // 忽略的文件或者文件夹
}

```
