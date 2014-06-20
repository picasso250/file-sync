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
function save_file($socket, $filename, $len)
{
    //读取客户端数据  
    echo "Read client data \n";
    //socket_read函数会一直读取客户端数据,直到遇见\n,\t或者\0字符.PHP脚本把这写字符看做是输入的结束符.  
    $buf = socket_read($socket, $len);
    echo "Received msg: $buf   \n";

    echo "save file $filename\n";
    return file_put_contents($filename, $buf);
}

function send_file($socket, $filename)
{
    echo "send file $filename\n";
    $content = file_get_contents($filename);
    socket_write($socket, $content) or die("Write failed\n"); // 数据传送 向服务器发送消息
}

function send_relet_file($socket, $root, $filename)
{
    if (strpos($filename, $root) !== 0) {
        echo "Error: filename $filename, root $root not match\n";
        return;
    }
    $relat_path = substr($filename, strlen($root)+1);
    echo "relat_path $relat_path\n";
    echo "send file $filename\n";
    $content = file_get_contents($filename);
    $ctrl = array(
        'cmd' => 'send file',
        'filename' => $relat_path,
        'size' => strlen($content),
    );
    $json = json_encode($ctrl);
    $len = strlen($json);
    echo "length of control message $len\n";
    $len = pack('i', $len);
    socket_write($socket, $len) or die("Write failed\n"); // 数据传送 向服务器发送消息  
    socket_write($socket, ($json)) or die("Write failed\n"); // 数据传送 向服务器发送消息  
    socket_write($socket, $content) or die("Write failed\n"); // 数据传送 向服务器发送消息
}

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

function save_relet_file($socket, $root)
{
    echo "Read client data \n";
    $len = socket_read($socket, 4);
    if (empty($len) || strlen($len) == 0) {
        return false;
    }
    $len = unpack('i', $len);
    $len = $len[1];
    echo "length of control message $len\n";
    $json = socket_read($socket, $len);
    $ctrl = json_decode($json);
    print_r($ctrl);
    if ($ctrl->cmd == 'end') {
        echo "recieve end\n";
        return -1;
    }
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

    socket_write($socket, 'recieve a file '.$filename);

    return save_file($socket, $filename, $ctrl->size);
}

