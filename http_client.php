<?php

require __DIR__.'/lib/lib.php';

set_time_limit(0);

$config = get_config();

$url = $config['url'];
$pairs = $config['pairs'];

$interval = 1;
$sleep = 0;
while (true) {
    foreach ($pairs as $id => $pair) {
        $root = $pair['root_client'];
        $ignore = $pair['ignore'];
        $changed = http_watch_dir($url, $id, $root, $ignore);
    }
    $sleep += $interval;
    if ($changed) {
        $sleep = 0;
    }
    echo "\rsleep $sleep s";
    sleep($interval);
}
