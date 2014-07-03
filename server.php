<?php

require __DIR__.'/lib/lib.php';

// 确保在连接客户端时不会超时
set_time_limit(0);

$config = get_config();

// 设置IP和端口号
$address = $config['host'];  
$port = $config['port']; // 调试的时候，可以多换端口来测试程序！
$root = $config['root_server'];

listen_on($address, $port, $root);
