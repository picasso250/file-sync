<?php

require __DIR__.'/php/tiny-frame/autoload.php';

run([
    ['GET', '/', function () {
        echo 'hello';
    }],
    ['POST', '/upload_file', 'upload_file']
]);

function upload_file()
{
    assert(isset($_REQUEST['dir']));
    $dirname = __DIR__.'/data/'.date('Ymd');
    if (is_file($dirname)) {
        echo 'Error: ', $dirname, ' is not dir', "\n";
        exit;
    }
    if (!is_dir($dirname)) {
        echo 'mkdir ', $dirname, "\n";
        mkdir($dirname, 0777, true);
    }

    $dir = $_REQUEST['dir'];
    $names = save_to_dir($dirname, $dir);
    foreach ($names as $name => $dst) {
    }
}

/**
 * 保存文件
 * 服务端调用
 */
function save_to_dir($dirname, $dir)
{
    assert(!empty($_FILES));
    foreach ($_FILES as $key => $f) {
        $f = $_FILES['f'];
        if ($f['error']) {
            die("upload error $f[error]");
        }
        $tmp_name = $f['tmp_name'];
        $md5 = md5_file($tmp_name);
        $filename = "$dirname/$md5";
        echo "save to $filename ";
        echo move_uploaded_file($tmp_name, $filename) ? 'ok' : 'fail';
        echo "\n";
        save_info("$dir/$f[name]", $filename, $_SERVER['REMOTE_ADDR']);
    }
    return;
}

function save_info($source, $dest, $addr)
{
    Service('db')->insert(compact('source', 'dest', 'addr'));
}
