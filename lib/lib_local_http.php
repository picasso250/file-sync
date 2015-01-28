<?php

/**
 * 监视文件夹
 * @param $host string 服务器地址
 * @param $port int 服务器端口
 * @param $root string 要监视的目录
 * @param $ignore array 忽略的文件
 * @return bool 是否被改变
 */
function http_watch_dir($url, $root, $server_root, $ignore)
{
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
                    $relat_path = substr(dirname($filename), strlen($root));
                    $changed = http_process_file($url, $filename, "$server_root/$relat_path", $changed);
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
function http_process_file($url, $filename, $dest, $changed)
{
    $mtime = modify_time($filename);
    if ($mtime != ($filemtime = filemtime($filename))) {
        modify_time($filename, $filemtime);
        echo "send file $filename to $dest\n";
        http_send_relet_file($url, $filename, $dest);
        return true;
    } else {
        return $changed;
    }
}

/**
 * 发送文件
 * @param $socket
 * @param $root
 * @param $filename
 */
function http_send_relet_file($url, $filename, $dest)
{
    $ch = curl_init($url);
    $ctrl = array(
        'action' => 'upload_file',
        'filename' => iconv('GBK', 'UTF-8', $filename),
        'dest' => $dest,
        'f' => new CURLFile($filename),
    );
    $options = [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $ctrl,
        CURLOPT_RETURNTRANSFER => 1,
    ];
    curl_setopt_array($ch, $options);
    $ret = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "code $code\n";
    echo $ret;
    curl_close($ch);
}

