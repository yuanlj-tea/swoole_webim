<?php
/**
 * Created by PhpStorm.
 * User: yuanlj
 * Date: 2017/12/7
 * Time: 16:28
 */

error_reporting(E_ALL ^ E_NOTICE); //error_reporting(E_ALL ^ E_NOTICE);//显示除去 E_NOTICE 之外的所有错误信息
define('STORAGE','file');
define('DOMAIN','http://192.168.79.206:8081');
define('ONLINE_DIR','/mnt/hgfs/swoole_webim/rooms/');

#房间配置
$rooms=[
    'a'=>'A',
    'b'=>'B',
    'c'=>'C',
    'd'=>'D',
    'e'=>'E',
    'f'=>'F',
];
