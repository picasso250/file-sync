<?php

require 'lib.php';

set_time_limit(0);

$config = get_config();

$host = $config['host'];
$port = $config['port'];
$root = $config['root_client'];

echo "on $root\n";

$ignore = $config['ignore'];
$interval = 1;
while (true) {
    $changed = watch_dir($host, $port, $root, $ignore);
    if (!$changed) {
        if ($interval < 10) {
            $interval++;
        }
    } else {
        $interval = 1;
    }
    echo "sleep $interval s\n";
    sleep($interval);
}
