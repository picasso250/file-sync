#!/bin/sh

mkdir ~/.file-sync
cd ~/.file-sync

wget "https://raw.githubusercontent.com/picasso250/file-sync/master/server.php"
wget "https://raw.githubusercontent.com/picasso250/file-sync/master/config.default.json"

mkdir lib
cd lib

wget "https://raw.githubusercontent.com/picasso250/file-sync/master/lib/lib.php"
wget "https://raw.githubusercontent.com/picasso250/file-sync/master/lib/lib_file.php"
wget "https://raw.githubusercontent.com/picasso250/file-sync/master/lib/lib_server.php"
wget "https://raw.githubusercontent.com/picasso250/file-sync/master/lib/lib_local.php"
wget "https://raw.githubusercontent.com/picasso250/file-sync/master/lib/lib_socket.php"

echo "install on ~/.file-sync"
