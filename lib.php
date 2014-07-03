<?php

/**
 * 获取配置
 * @return array|mixed|string
 */
function get_config()
{
    $config_file = __DIR__.'/config.default.json';
    if (!is_file($config_file)) {
        echo "$config_file not exists\n";
        exit(1);
    }
    $config = file_get_contents($config_file);
    $config = json_decode($config, true);
    $f = __DIR__.'/config.user.json';
    if (is_file($f)) {
        $config_user = json_decode(file_get_contents($f), true);
        if (isset($config_user['ignore'])) {
            $ignore = array_merge($config['ignore'], $config_user['ignore']);
        }
        $config = array_merge($config, $config_user);
        if (isset($ignore)) {
            $config['ignore'] = $ignore;
        }
    }
    return $config;
}

/**
 * 读取足够的字节数
 * @param $socket
 * @param $len
 * @return string
 */
function socket_read_enough($socket, $len)
{
    if (!$len) {
        return '';
    }
    $ret = socket_read($socket, $len);
    while (strlen($ret) != $len && $len > 0) {
        echo "read more\n";
        $len -= strlen($ret);
        $ret .= socket_read($socket, $len);
    }
    return $ret;
}

/**
 * 写入足够的字节
 * @param $socket
 * @param $st
 * @return bool
 */
function socket_write_big($socket, $st)
{
    $length = strlen($st);
    while (true) {
        $sent = socket_write($socket, $st, $length);
        if ($sent === false) {
            break;
        }
        // Check if the entire message has been sented
        if ($sent < $length) {
            // If not sent the entire message.
            // Get the part of the message that has not yet been sented as message
            $st = substr($st, $sent);
            // Get the length of the not sented part
            $length -= $sent;
        } else {
            break;
        }
    }
    return true;
}


/**
 * 是否是文本文件
 * @param $filename
 * @return bool
 */
function is_text_file($filename)
{
    return !preg_match('/\.png$|\.jpg|\.gif$|\.eot$|\.woff$|\.ttf$/i', $filename);
}





/**
 * 载入最后修改时间表
 * @return array|mixed
 */
function load_modify_time()
{
    $f = __DIR__.'/modify_time';
    if (is_file($f)) {
        return json_decode(file_get_contents($f), true);
    }
    return array();
}

/**
 * 保存最后修改时间表
 * @param $modify_table
 * @return int
 */
function save_modify_time($modify_table)
{
    $f = __DIR__.'/modify_time';
    return file_put_contents($f, json_encode($modify_table));
}

include __DIR__.'/lib_server.php';
include __DIR__.'/lib_local.php';