<?php

require __DIR__.'/lib/lib.php';

set_time_limit(0);

$config = get_config();

$pairs = $config['pairs'];

$interval = 1;
$sleep = 0;
while (true) {
    foreach ($pairs as $id => $pair) {
        $url = $pair['url'];
        $root = $pair['root_client'];
        $ignore = $pair['ignore'];
        $changed = http_watch_dir($url, $root, $pair['root_server'], $ignore);
    }
    $sleep += $interval;
    if ($changed) {
        $sleep = 0;
    }
    echo "\rsleep $sleep s";
    sleep($interval);
}
