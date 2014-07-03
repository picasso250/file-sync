<?php
/**
 * Created by PhpStorm.
 * User: wangxiaochi
 * Date: 14-7-3
 * Time: 下午1:18
 */

/**
 * 监视文件夹
 * @param $host string 服务器地址
 * @param $port int 服务器端口
 * @param $root string 要监视的目录
 * @param $ignore array 忽略的文件
 * @return bool 是否被改变
 */
function watch_dir($host, $port, $root, $ignore)
{
    $socket = null;
    $changed = false;

    $modify_table = load_modify_time();
    $queue = array($root);

    while (!empty($queue)) {
        // echo "queue\n"; var_dump($queue);
        $root_dir = array_shift($queue);
        // echo "enter dir $root_dir\n";
        $d = opendir($root_dir);

        while (($f = readdir($d)) !== false) {
            if ($f == '.' || $f == '..') {
                continue;
            }
            if (in_array($f, $ignore)) {
                // echo "skip $f\n";
                continue;
            }
            $filename = "$root_dir/$f";
            if (is_file($filename)) {
                if (!isset($modify_table[$filename])) {
                    $filemtime = filemtime($filename);
                    list($modify_table, $socket, $changed) = send_file_change($host, $port, $root, $filemtime, $modify_table, $filename, $socket);
                } else {
                    $filemtime = filemtime($filename);
                    if ($modify_table[$filename] != $filemtime) {
                        list($modify_table, $socket, $changed) = send_file_change($host, $port, $root, $filemtime, $modify_table, $filename, $socket);
                    } else {
                        // echo ".";
                    }
                }
            } elseif (is_dir($filename)) {
                // echo "add to queue $filename\n";
                $queue[] = "$filename";
            }
        }
    }
    // echo "ok\n";
    save_modify_time($modify_table);

    if ($socket !== null) {
        end_socket($socket);
    }
    return $changed;
}


/**
 * 发送改变了的文件
 * @param $host
 * @param $port
 * @param $root
 * @param $filemtime
 * @param $modify_table
 * @param $filename
 * @param $socket
 * @return array
 */
function send_file_change($host, $port, $root, $filemtime, $modify_table, $filename, $socket)
{
    $modify_table[$filename] = $filemtime;
    echo "time diff $modify_table[$filename] $filemtime\n";
    echo "send file $filename\n";
    if ($socket === null) {
        $socket = open_socket($host, $port);
    }
    send_relet_file($socket, $root, $filename);
    $changed = true;
    return array($modify_table, $socket, $changed);
}



/**
 * 打开和服务器端的连接
 * @param $host
 * @param $port
 * @return resource
 */
function open_socket($host, $port)
{
    echo "Connect to $host:$port ... \n";
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create socket\n"); // 创建一个Socket
    $connection = socket_connect($socket, $host, $port) or die("Could not connet server\n");    //  连接
    return $socket;
}

/**
 *
 * @param $socket
 */
function end_socket($socket)
{
    send_end($socket);
    while ($buff = socket_read($socket, 1024)) {
        echo("Response was:" . $buff . "\n");
    }
    socket_close($socket);
}



/**
 * 发送结束命令
 * @param $socket
 */
function send_end($socket)
{
    echo "send end\n";
    $ctrl = array(
        'cmd' => 'end',
    );
    $json = json_encode($ctrl);
    $len = strlen($json);
    echo "length of control message $len\n";
    $len = pack('i', $len);
    socket_write($socket, $len) or die("Write failed\n"); // 数据传送 向服务器发送消息
    socket_write($socket, ($json)) or die("Write failed\n"); // 数据传送 向服务器发送消息
}