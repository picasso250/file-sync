<?php
 
set_time_limit(0);  
  
$host = "127.0.0.1";  
$port = 2048;
$root = __DIR__.'/client';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create  socket\n"); // 创建一个Socket  
   
$connection = socket_connect($socket, $host, $port) or die("Could not connet server\n");    //  连接  

$filename = "$root/test.txt";
socket_write($socket, file_get_contents($filename)) or die("Write failed\n"); // 数据传送 向服务器发送消息  
while ($buff = socket_read($socket, 1024)) {  
    echo("Response was:" . $buff . "\n");
}  
socket_close($socket);
