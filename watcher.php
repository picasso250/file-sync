<?php
/**
 * 监视本地文件的改变，并将改变通知服务器
 */

require __DIR__.'/lib/lib.php';

set_time_limit(0);

$config = get_config();

$host = $config['host'];
$port = $config['port'];
$pairs = $config['pairs'];

foreach ($pairs as $id => $pair) {
    $root = $pair['root_client'];
    echo "on $root\n";
}

$interval = 1;
$sleep = 0;
$changed = false;
while (true) {
    foreach ($pairs as $id => $pair) {
        $root = $pair['root_client'];
        $ignore = $pair['ignore'];
        $changed |= watch_dir($host, $port, $id, $root, $ignore);
    }
    if ($changed) {
        $sleep = $interval;
        $changed = false;
        echo "\n";
    } else {
        $sleep++;
    }
    echo "\rsleep $sleep s";
    sleep($interval);
}
