<?php

require 'lib.php';

set_time_limit(0);
  
$host = "127.0.0.1";  
$port = 2048;
$root = __DIR__.'/client';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create  socket\n"); // 创建一个Socket

$connection = socket_connect($socket, $host, $port) or die("Could not connet server\n");    //  连接  

$queue = array($root);

while (!empty($queue)) {
    echo "queue\n";
    var_dump($queue);
    $root_dir = array_shift($queue);
    echo "enter dir $root_dir\n";
    $d = opendir($root_dir);

    while (($f = readdir($d)) !== false) {
        if ($f == '.' || $f == '..' || $f == '.git' || $f == '.svn') {
            continue;
        }
        $filename = "$root_dir/$f";
        if (is_file($filename)) {
            echo "send file $filename\n";
            send_relet_file($socket, $root, $filename);
        } elseif (is_dir($filename)) {
            echo "add to queue $filename\n";
            $queue[] = "$filename";
        }
    }
}
send_end($socket);
echo "ok\n";

while ($buff = socket_read($socket, 1024)) {  
    echo("Response was:" . $buff . "\n");
}
socket_close($socket);
