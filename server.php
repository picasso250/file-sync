<?php

require 'lib.php';

//确保在连接客户端时不会超时  
set_time_limit(0);

$config = get_config();

//设置IP和端口号  
$address = $config['host'];  
$port = $config['port']; //调试的时候，可以多换端口来测试程序！  
$root = $config['root_server'];

/** 
 * 创建一个SOCKET  
 * AF_INET=是ipv4 如果用ipv6，则参数为 AF_INET6 
 * SOCK_STREAM为socket的tcp类型，如果是UDP则使用SOCK_DGRAM 
*/  
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");  
//阻塞模式  
socket_set_block($sock) or die("socket_set_block() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");  
//绑定到socket端口
$result = socket_bind($sock, $address, $port) or die("socket_bind() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");  
//开始监听  
$result = socket_listen($sock, 4) or die("socket_listen() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");  
echo "on $root\n";
echo "Binding the socket on $address:$port ... ";
echo "OK\nNow ready to accept connections.\nListening on the socket ... \n";

do { // never stop the daemon  
    //它接收连接请求并调用一个子连接Socket来处理客户端和服务器间的信息  
    $msgsock = socket_accept($sock) or  die("socket_accept() failed: reason: " . socket_strerror(socket_last_error()) . "/n");  
      
    //读取客户端数据  
    while (save_relet_file($msgsock, $root) !== -1) {
        echo "ok\n";
    }

    //数据传送 向客户端写入返回结果
    $json = json_encode(array('code' => 0, 'msg' => 'OK'));
    socket_write($msgsock, $json, strlen($json)) or die("socket_write() failed: reason: " . socket_strerror(socket_last_error()) ."/n");  
    //一旦输出被返回到客户端,父/子socket都应通过socket_close($msgsock)函数来终止  
    socket_close($msgsock);  
} while (true);

socket_close($sock);
