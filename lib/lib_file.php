<?php
/**
 * Created by PhpStorm.
 * User: wangxiaochi
 * Date: 14-7-3
 * Time: 下午1:27
 */

/**
 * 是否是文本文件
 * @param $filename
 * @return bool
 */
function is_text_file($filename)
{
    // 现在是根据后缀名来判断。但这样恐怕多有不妥
    return !preg_match('/\.(png|jpg|gif|eot|woff|ttf|gz|tar|bz2|zip|rar|7z)$/i', $filename);
}

/**
 * 载入最后修改时间表
 * @return array|mixed
 */
function load_modify_time()
{
    $f = modify_time_file();
    if (is_file($f)) {
        return json_decode(file_get_contents($f), true) ?: array();
    }
    return array();
}

/**
 * 保存最后修改时间表
 * @param $modify_table
 * @return int
 */
function save_modify_time($modify_table)
{
    $f = modify_time_file();
    return file_put_contents($f, json_encode($modify_table));
}

function modify_time_file()
{
    $f = __DIR__.'/modify_time_'.($GLOBALS['id']);
    return $f;
}
