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

    file_put_contents($filename, $buf);
}
