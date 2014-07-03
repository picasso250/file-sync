<?php
/**
 * Created by PhpStorm.
 * User: wangxiaochi
 * Date: 14-7-3
 * Time: 下午1:27
 */


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