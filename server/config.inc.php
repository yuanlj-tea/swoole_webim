<?php
/**
 * Created by PhpStorm.
 * User: yuanlj
 * Date: 2017/12/7
 * Time: 16:28
 */

error_reporting(E_ALL ^ E_NOTICE);
//define('STORAGE','file');
//define('DOMAIN', 'http://192.168.79.206:8081');
define('DOMAIN', 'http://www.webim.com');
//define('ONLINE_DIR','/mnt/hgfs/swoole_webim/rooms/');

#房间配置
$rooms = [
    '1' => '1',
    '2' => '2',
    '3' => '3',
    '4' => '4',
    '5' => '5',
    '6' => '6',
];

$redis_conf = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => 123456
];
