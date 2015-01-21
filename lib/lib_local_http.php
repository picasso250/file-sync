<?php

/**
 * 监视文件夹
 * @param $host string 服务器地址
 * @param $port int 服务器端口
 * @param $root string 要监视的目录
 * @param $ignore array 忽略的文件
 * @return bool 是否被改变
 */
function http_watch_dir($url, $id, $root, $ignore)
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
                    list($modify_table, $changed) = http_process_file($url, $id, $root, $modify_table, $filename, $changed);
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
function http_process_file($url, $id, $root, $modify_table, $filename, $changed)
{
    if (modify_time($filename) === null) {
        $filemtime = filemtime($filename);
        list($modify_table, $changed) = http_send_file_change($url, $id, $root, $filemtime, $modify_table, $filename);
        return array($modify_table, $changed);
    } else {
        $filemtime = filemtime($filename);
        if (modify_time($filename) != $filemtime) {
            list($modify_table, $changed) = http_send_file_change($url, $id, $root, $filemtime, $modify_table, $filename);
        } else {
            // echo ".";
        }
        return array($modify_table, $changed);
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
function http_send_file_change($url, $id, $root, $filemtime, $modify_table, $filename)
{
    modify_time($filename, $filemtime);
    // echo "time diff $modify_table[$filename] $filemtime\n";
    echo "send file $filename\n";
    http_send_relet_file($url, $id, $root, $filename);
    $changed = true;
    return array($modify_table, $changed);
}

/**
 * 发送文件
 * @param $socket
 * @param $root
 * @param $filename
 */
function http_send_relet_file($url, $id, $root, $filename)
{
    if (strpos($filename, $root) !== 0) {
        echo "Error: filename $filename, root $root not match\n";
        return;
    }
    $ch = curl_init($url);
    $relat_path = substr($filename, strlen($root)+1);
    echo "relat_path $relat_path\n";
    echo "send file $filename\n";
    $ctrl = array(
        'action' => 'upload_file',
        'filename' => iconv('GBK', 'UTF-8', $relat_path),
        'id' => $id,
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

