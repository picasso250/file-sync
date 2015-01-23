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
    $dirname = $_REQUEST['dest'];

    if (is_file($dirname)) {
        echo 'Error: ', $dirname, ' is not dir', "\n";
        exit;
    }
    if (!is_dir($dirname)) {
        echo 'mkdir ', $dirname, "\n";
        mkdir($dirname, 0777, true);
    }

    return save_to_dir($dirname);
}

/**
 * 保存文件
 * 服务端调用
 */
function save_to_dir($dirname)
{
    assert(!empty($_FILES));
    foreach ($_FILES as $key => $f) {
        $f = $_FILES['f'];
        if ($f['error']) {
            die("upload error $f[error]");
        }
        $filename = "$dirname/$f[name]";
        echo "save to $filename ";
        $tmp_name = $f['tmp_name'];
        echo move_uploaded_file($tmp_name, $filename) ? 'ok' : 'fail';
        echo "\n";
    }
    return;
}
