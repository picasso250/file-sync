<?php

if (!isset($_REQUEST['action'])) {
    echo "no action\n";
    exit;
}

$action = $_REQUEST['action'];
$action();

function upload_file()
{
    assert(isset($_REQUEST['dest']));
    $filename = $_REQUEST['dest'];

    $dirname = dirname($filename);
    if (is_file($dirname)) {
        echo 'Error: ', $filename, ' is not dir', "\n";
        exit;
    }
    if (!is_dir($dirname)) {
        echo 'mkdir ', $dirname, "\n";
        mkdir($dirname, 0777, true);
    }

    return http_save_file($filename);
}

/**
 * 保存文件
 * 服务端调用
 */
function http_save_file($filename)
{
    assert(isset($_FILES['f']));
    $f = $_FILES['f'];
    if ($f['error']) {
        die("upload error $f[error]");
    }
    $tmp_name = $f['tmp_name'];
    echo "save to $filename ";
    echo move_uploaded_file($tmp_name, $filename) ? 'ok' : 'fail';
    echo "\n";
    return;
}
