<?php
/**
 * 帮助服务器收集散落在天涯的文件
 */

if ($argc == 1) {
    echo "Usage:\n\tsend '*.log' '*.wf'\n";
}

$pattern_list = get_pattern_list();

require __DIR__.'/lib/lib.php';

set_time_limit(0);

$config = get_config();

$host = $config['host'];
$port = $config['port'];
$root = $config['root_client'];

$socket = open_socket($host, $port);

foreach ($pattern_list as $pattern) {
    foreach (glob($pattern) as $filename) {
        send_relet_file($socket, $root, $root.'/'.$filename);
    }
}
