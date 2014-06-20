<?php

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
 * 保存文件
 * 服务端调用
 */
function save_file($socket, $filename)
{
    //读取客户端数据  
    echo "Read client data \n";  
    //socket_read函数会一直读取客户端数据,直到遇见\n,\t或者\0字符.PHP脚本把这写字符看做是输入的结束符.  
    $buf = socket_read($socket, 8192);  
    echo "Received msg: $buf   \n";

    echo "save file $filename\n";
    file_put_contents($filename, $buf);
}

function send_file($socket, $filename)
{
    echo "send file $filename\n";
    socket_write($socket, file_get_contents($filename)) or die("Write failed\n"); // 数据传送 向服务器发送消息
}

function send_relet_file($socket, $relat_path)
{
    $filename = "$root/$relat_path";
    $ctrl = array('filename' => $relat_path);
    $json = json_encode($ctrl);
    $len = strlen($json);
    echo "length of control message $len\n";
    $len = pack('i', $len);
    socket_write($socket, $len) or die("Write failed\n"); // 数据传送 向服务器发送消息  
    socket_write($socket, ($json)) or die("Write failed\n"); // 数据传送 向服务器发送消息  
    return send_file($socket, $filename);
}

function save_relet_file($socket, $root)
{
    echo "Read client data \n";
    $len = socket_read($socket, 4);
    $len = unpack('i', $len);
    $len = $len[1];
    echo "length of control message $len\n";
    $json = socket_read($socket, $len);
    $ctrl = json_decode($json);
    print_r($ctrl);
    $filename = "$root/$ctrl->filename";

    $dirname = dirname($filename);
    if (is_file($dirname)) {
        echo 'Error: ', $filename, ' is not dir', "\n";
        exit;
    }
    if (!is_dir($dirname)) {
        echo 'mkdir ', $dirname, "\n";
        mkdir($dirname, 0777, true);
    }

    return save_file($socket, $filename);
}
