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

$modify_table = load_modify_time();
$queue = array($root);

while (!empty($queue)) {
    // echo "queue\n"; var_dump($queue);
    $root_dir = array_shift($queue);
    // echo "enter dir $root_dir\n";
    $d = opendir($root_dir);

    while (($f = readdir($d)) !== false) {
        if (in_array($f, array('.', '..', '.git', '.svn', '.idea'))) {
            continue;
        }
        $filename = "$root_dir/$f";
        if (is_file($filename)) {
            if (!isset($modify_table[$filename])) {
                $modify_table[$filename] = filemtime($filename);
            } else {
                $filemtime = filemtime($filename);
                if ($modify_table[$filename] != $filemtime) {
                    $modify_table[$filename] = $filemtime;
                    echo "time diff $modify_table[$filename] $filemtime\n";
                    echo "send file $filename\n";
                    send_relet_file($socket, $root, $filename);
                } else {
                    echo ".";
                }
            }
        } elseif (is_dir($filename)) {
            // echo "add to queue $filename\n";
            $queue[] = "$filename";
        }
    }
}
send_end($socket);
echo "ok\n";
save_modify_time($modify_table);

while ($buff = socket_read($socket, 1024)) {  
    echo("Response was:" . $buff . "\n");
}
socket_close($socket);
