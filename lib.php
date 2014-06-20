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
