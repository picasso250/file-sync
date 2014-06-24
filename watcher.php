<?php

require 'lib.php';

set_time_limit(0);

$config = get_config();

$host = $config['host'];
$port = $config['port'];
$root = $config['root_client'];

echo "on $root\n";
echo "Connect to $host:$port ... \n";

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create  socket\n"); // 创建一个Socket
$connection = socket_connect($socket, $host, $port) or die("Could not connet server\n");    //  连接

$ignore = $config['ignore'];

watch_dir($socket, $root, $ignore);

socket_close($socket);
