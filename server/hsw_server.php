<?php
ini_set('date.timezone','Asia/Shanghai');


require_once "config.inc.php";
require_once "emotion.config.php";
require_once "functions.php";
require_once "Predis.php";
//require_once "classes/" . STORAGE . "/ChatBase.class.php";
//require_once "classes/" . STORAGE . "/File.class.php";
//require_once "classes/" . STORAGE . "/ChatUser.class.php";
//require_once "classes/" . STORAGE . "/ChatLine.class.php";
//require_once "classes/Chat.class.php";
require_once "classes/hsw.class.php";
require_once "classes/Room.class.php";

$server = new hsw();