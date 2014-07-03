<?php

/**
 * 获取配置
 * @return array|mixed|string
 */
function get_config()
{
    $config_file = __DIR__.'/config.default.json';
    if (!is_file($config_file)) {
        echo "$config_file not exists\n";
        exit(1);
    }
    $config = file_get_contents($config_file);
    $config = json_decode($config, true);
    $f = __DIR__.'/config.user.json';
    if (is_file($f)) {
        $config_user = json_decode(file_get_contents($f), true);
        if (isset($config_user['ignore'])) {
            $ignore = array_merge($config['ignore'], $config_user['ignore']);
        }
        $config = array_merge($config, $config_user);
        if (isset($ignore)) {
            $config['ignore'] = $ignore;
        }
    }
    return $config;
}

include __DIR__.'/lib_file.php';
include __DIR__.'/lib_socket.php';
include __DIR__.'/lib_server.php';
include __DIR__.'/lib_local.php';