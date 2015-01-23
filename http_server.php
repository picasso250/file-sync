<?php

if (!isset($_REQUEST['action'])) {
    echo "no action\n";
    exit;
}

$action = $_REQUEST['action'];
$action();

function upload_file()
{
    $config = get_config();
    $pairs = $config['pairs'];

    http_save_relet_file($pairs);
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

/**
 * 接收文件
 * @param $socket
 * @param $pairs
 * @return bool|int|void
 */
function http_save_relet_file($pairs, $use_ip = false)
{
    $root = $pairs[$_REQUEST['id']]['root_server'];
    $filename = "$root/".str_replace('\\', '/', $_REQUEST['filename']);

    $dirname = dirname($filename);
    if (is_file($dirname)) {
        echo 'Error: ', $filename, ' is not dir', "\n";
        exit;
    }
    if (!is_dir($dirname)) {
        echo 'mkdir ', $dirname, "\n";
        mkdir($dirname, 0777, true);
    }

    print_r(['cmd' => 'recieve', 'file' => $filename]);

    return http_save_file($filename);
}
