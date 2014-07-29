<?php
/**
 * 帮助服务器收集散落在天涯的文件
 */

if ($argc == 1) {
    echo "Usage:\n\tsend \"*.log\" \"*.wf\"\n";
    exit(1);
}

require __DIR__.'/lib/lib.php';

set_time_limit(0);

$config = get_config();

$host = $config['host'];
$port = $config['port'];
$root = $config['root_client'];

$socket = open_socket($host, $port);

$pattern_list = get_pattern_list($argv);
foreach ($pattern_list as $pattern) {
    echo "for pattern $pattern\nls $root/$pattern\n";
    $file_list = glob("$root/$pattern");
    foreach ($file_list as $filename) {
        echo "$filename\n";
        send_relet_file($socket, $root, $filename);
    }
    send_end($socket);
}
