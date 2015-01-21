<?php

require __DIR__.'/lib/lib.php';

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
