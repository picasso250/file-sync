file-sync
=========

将本地的文件实时（每隔1秒）的传到服务器上的指定目录。

原理
------
每隔一秒，检测本地文件的mtime，如有变化，传到服务器。服务器开端口监听。将收到的文件写入指定位置。

用法
------

在服务器上打开 `server.php` 。在本地打开 `watcher.php` 或 `watcher.py`

`watcher.py`需要python3才能使用。

配置
-------

`config.default.json` 使用json格式

你也可以使用 `config.user.json` 文件，此文件中的配置会覆盖 `config.default.json`

```json
{
    "host": "127.0.0.1", // 监听的IP
    "port": 5666,        // 端口
    "pairs": [ // 支持多个服务器-客户端文件夹的配对
        {
            "root_server": "/home/work", // 服务器的根目录
            "root_client": "D:/work", // 客户端的监视的根目录
            "ignore": [".git", ".svn", ".idea"] // 忽略的文件或者文件夹
        }
    ]
}

```

更新
------

- 2014年8月8日 支持多个服务器-客户端文件夹的配对

