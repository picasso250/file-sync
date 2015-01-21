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
function watch_dir($host, $port, $id, $root, $ignore)
{
    $socket = null;
    $changed = false;

    $modify_table = load_modify_time();
    if (!is_dir($root)) {
        echo "$root not dir\n";
        exit(1);
    }
    $queue = array($root);

    $t = -microtime(true);
    while (!empty($queue)) {
        // echo "queue\n"; var_dump($queue);
        $root_dir = array_shift($queue);
        // echo "enter dir $root_dir\n";
        $d = opendir($root_dir);
        if ($d === false) {
            echo "$root_dir not exists\n";
            exit(1);
        }

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
                $filesize = filesize($filename);
                assert($filesize !== false);
                if ($filesize > 100 * 1024 * 1024) {
                    // big than 100M
                    echo "skip $filename with size $filesize\n";
                } else {
                    list($modify_table, $socket, $changed) = process_file($host, $port, $id, $root, $modify_table, $filename, $socket, $changed);
                }
            } elseif (is_dir($filename)) {
                // echo "add to queue $filename\n";
                $queue[] = "$filename";
            }
        }
    }
    $t += microtime(true);
    echo " ($root " . intval($t*1000) . " ms)";
    // echo "ok\n";
    save_modify_time(modify_time());

    if ($socket !== null) {
        end_socket($socket);
    }
    return $changed;
}

/**
 * 处理文件
 * @param $host
 * @param $port
 * @param $root
 * @param $modify_table
 * @param $filename
 * @param $socket
 * @return array
 */
function process_file($host, $port, $id, $root, $modify_table, $filename, $socket, $changed)
{
    if (modify_time($filename) === null) {
        $filemtime = filemtime($filename);
        list($modify_table, $socket, $changed) = send_file_change($host, $port, $id, $root, $filemtime, $modify_table, $filename, $socket);
        return array($modify_table, $socket, $changed);
    } else {
        $filemtime = filemtime($filename);
        if (modify_time($filename) != $filemtime) {
            list($modify_table, $socket, $changed) = send_file_change($host, $port, $id, $root, $filemtime, $modify_table, $filename, $socket);
        } else {
            // echo ".";
        }
        return array($modify_table, $socket, $changed);
    }
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
function send_file_change($host, $port, $id, $root, $filemtime, $modify_table, $filename, $socket)
{
    modify_time($filename, $filemtime);
    // echo "time diff $modify_table[$filename] $filemtime\n";
    echo "send file $filename\n";
    if ($socket === null) {
        $socket = open_socket($host, $port);
    }
    send_relet_file($socket, $id, $root, $filename);
    $changed = true;
    return array($modify_table, $socket, $changed);
}

function modify_time($key = null, $value = null)
{
    static $modify_table;
    if (empty($modify_table)) {
        $modify_table = load_modify_time();
    }
    if ($key === null) {
        return $modify_table;
    }
    if (PHP_OS === 'WINNT') {
        $key = iconv('GBK', 'UTF-8', $key);
    }
    if ($value === null) {
        return isset($modify_table[$key]) ? $modify_table[$key] : null;
    }
    $modify_table[$key] = $value;
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
 * 结束连接
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
    socket_write_enough($socket, $len) or die("Write failed\n"); // 数据传送 向服务器发送消息
    socket_write_enough($socket, $json) or die("Write failed\n"); // 数据传送 向服务器发送消息
}


/**
 * 发送文件
 * @param $socket
 * @param $root
 * @param $filename
 */
function send_relet_file($socket, $id, $root, $filename)
{
    if (strpos($filename, $root) !== 0) {
        echo "Error: filename $filename, root $root not match\n";
        return;
    }
    $relat_path = substr($filename, strlen($root)+1);
    echo "relat_path $relat_path\n";
    echo "send file $filename\n";
    $content = file_get_contents($filename);
    if (is_text_file($filename)) {
        $content = str_replace(PHP_EOL, "\n", $content);
    }
    // echo "$content\n";
    $size = strlen($content);
    if ($size == 0) {
        var_dump($content);
        echo "emtpy file\n";
    }
    $ctrl = array(
        'cmd' => 'send file',
        'filename' => iconv('GBK', 'UTF-8', $relat_path),
        'size' => $size,
        'id' => $id,
    );
    // var_dump($ctrl);
    $json = json_encode($ctrl);
    if (json_last_error()) {
        echo "json encode error ", json_last_error(),"\n";
    }
    $len = strlen($json);
    echo "length of control message $len\n";
    $len = pack('i', $len);
    socket_write($socket, $len) or die("Write failed in ".__FUNCTION__."():".__LINE__."\n"); // 数据传送 向服务器发送消息
    if (true !== socket_write_enough($socket, $json)) {
        echo ("Write failed in ".__FUNCTION__."():".__LINE__."\n");
    }
    if (true !== socket_write_enough($socket, $content)) {
        echo ("Write failed in ".__FUNCTION__."():".__LINE__."\n");
    }
}
