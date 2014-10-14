<?php
/**
 * Created by PhpStorm.
 * User: wangxiaochi
 * Date: 14-7-3
 * Time: 下午1:19
 */

/**
 * 保存文件
 * 服务端调用
 */
function save_file($socket, $filename, $len)
{
    $tmpfile = $filename.'.tmp.'.microtime();
    $f = fopen($tmpfile, 'w');
    // 读取客户端数据
    echo "Read client data \n";
    // socket_read函数会一直读取客户端数据,直到遇见\n,\t或者\0字符.PHP脚本把这些字符看做是输入的结束符.
    $buf = socket_read($socket, $len);
    fwrite($f, $buf);
    while (strlen($buf) != $len && $len > 0) {
        $len -= strlen($buf);
        $buf = socket_read($socket, $len);
        fwrite($f, $buf);
        // echo "write: $buf   \n";
    }
    fclose($f);
    echo "save file $tmpfile\n";
    rename($tmpfile, $filename);
    echo "save file $filename\n";

    return;
}


/**
 * 接收文件
 * @param $socket
 * @param $pairs
 * @return bool|int|void
 */
function save_relet_file($socket, $pairs, $use_ip = false)
{
    echo "Read client data \n";
    $len = socket_read_enough($socket, 4);
    if (empty($len) || strlen($len) == 0) {
        return false;
    }
    $len = unpack('i', $len);
    $len = $len[1];
    echo "length of control message $len\n";
    if ($len > 10000) {
        echo "length too long, stop\n";
        exit;
    }
    $json = socket_read_enough($socket, $len);
    $ctrl = json_decode($json);
    if (empty($ctrl)) {
        var_dump($json);
        echo "ctrl obj emtpy\n";
        exit();
    }
    if ($ctrl->cmd == 'end') {
        echo "recieve end\n";
        return -1;
    }
    $root = $pairs[$ctrl->id]['root_server'];
    if ($use_ip) {
        if (!socket_getpeername($socket, $ip)) {
            echo "can not get ip of client\n";
            exit;
        }
        echo "IP $ip\n";
        $root = "$root/$ip";
        if (!is_dir($root)) {
            mkdir($root);
        }
    }
    $filename = "$root/".str_replace('\\', '/', $ctrl->filename);

    $dirname = dirname($filename);
    if (is_file($dirname)) {
        echo 'Error: ', $filename, ' is not dir', "\n";
        exit;
    }
    if (!is_dir($dirname)) {
        echo 'mkdir ', $dirname, "\n";
        mkdir($dirname, 0777, true);
    }

    $info = (['cmd' => 'recieve', 'file' => $filename]);
    reply($socket, $info);

    return save_file($socket, $filename, $ctrl->size);
}

function reply($socket, $info)
{
    $j = json_encode($info);
    $l = pack('i', strlen($j));
    return socket_write($socket, $l.$j);
}

/**
 * 端口
 * @param $address
 * @param $port
 * @param $pairs
 */
function listen_on($address, $port, $pairs, $use_ip = false)
{
    /**
     * 创建一个SOCKET
     * AF_INET=是ipv4 如果用ipv6，则参数为 AF_INET6
     * SOCK_STREAM为socket的tcp类型，如果是UDP则使用SOCK_DGRAM
     */
    $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
    // 阻塞模式
    socket_set_block($sock) or die("socket_set_block() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
    // 绑定到socket端口
    $result = socket_bind($sock, $address, $port) or die("socket_bind() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
    //开始监听
    $result = socket_listen($sock, 4) or die("socket_listen() 失败的原因是:" . socket_strerror(socket_last_error()) . "/n");
    echo "Binding the socket on $address:$port ... ";
    echo "OK\nListening on the socket $address:$port ... \n";

    do { // never stop the daemon
        // 它接收连接请求并调用一个子连接Socket来处理客户端和服务器间的信息
        $msgsock = socket_accept($sock) or die("socket_accept() failed: reason: " . socket_strerror(socket_last_error()) . "/n");

        // 读取客户端数据
        while (save_relet_file($msgsock, $pairs, $use_ip) !== -1) {
            echo "ok\n";
        }

        // 数据传送 向客户端写入返回结果
        $info = (array('code' => 0, 'msg' => 'OK'));
        reply($msgsock, $info);
        // 一旦输出被返回到客户端,父/子socket都应通过socket_close($msgsock)函数来终止
        socket_close($msgsock);
    } while (true);

    socket_close($sock);
}
