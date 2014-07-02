<?php
/**
 * 监视本地文件的改变，并将改变通知服务器
 */

require 'lib.php';

set_time_limit(0);

$config = get_config();

$host = $config['host'];
$port = $config['port'];
$root = $config['root_client'];

echo "on $root\n";

$ignore = $config['ignore'];
$interval = 1;
$sleep = 0;
while (true) {
    $changed = watch_dir($host, $port, $root, $ignore);
    if ($changed) {
        $sleep = $interval;
        echo "\n";
    } else {
        $sleep++;
    }
    echo "\rsleep $sleep s";
    sleep($interval);
}
