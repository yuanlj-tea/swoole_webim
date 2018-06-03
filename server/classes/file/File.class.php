<?php

/**
 * Created by PhpStorm.
 * User: yuanlj
 * Date: 2017/12/7
 * Time: 17:43
 */
class File
{
    private static $instance;
    protected $online_dir;
    protected $history = [];
    protected $history_max_size = 100;
    protected $history_write_count = 0;

    private function __construct()
    {
        global $rooms;
        $this->online_dir = ONLINE_DIR;
        foreach ($rooms as $k => $v) {
            $this->checkDir($this->online_dir . $k . DIRECTORY_SEPARATOR, true);
        }
    }

    public static function checkDir($dir, $clear_file = false)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                rw_deny:
                trigger_error("can not read/write dir[\".$dir.\"]\", E_ERROR");
                return;
            }
        } elseif (!$clear_file) {
            self::clearDir($dir);
        }
    }

    public static function clearDir($dir)
    {
        $n = 0;
        if ($dh = opendir($dir)) {
            while ($file = read_dir($dir) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_file($dir . $file)) {
                    unlink($dir . $file);
                    $n++;
                }
                if (is_dir($dir . $file)) {
                    self::clearDir($dir . $file . DIRECTORY_SEPARATOR);
                    $n++;
                }
            }
            closedir($dh);

        }
        return $n;
    }

    public static function init()
    {
//        if(!self::$instance instanceof self){
//            self::$instance = new self();
//        }
//        return self::$instance;
        if (self::$instance instanceof self) {
            return false;
        }
        self::$instance = new self();
    }

    public static function changeUser($oldroomid, $fd, $newroomid)
    {
        $old = self::$instance->online_dir . $oldroomid . DIRECTORY_SEPARATOR . $fd;
        $new = self::$instance->online_dir . $newroomid . DIRECTORY_SEPARATOR . $fd;
        $return = copy($old, $new);//拷贝到新目录
        unlink($old);//删除旧目录下的文件
        return $return;
    }

    //登录
    public static function login($roomid, $fd, $info)
    {
        $flag = file_put_contents(self::$instance->online_dir . $roomid . DIRECTORY_SEPARATOR . $fd, @serialize($info));
        return $flag;
    }

    /**
     * 获取所有房间的在线用户
     */
    public static function getOnlineUsers()
    {
        global $rooms;
        $online_users=[];

        foreach($rooms as $k=>$v){
            $online_users[$k]=array_slice(scandir(self::$instance->online_dir.$k.DIRECTORY_SEPARATOR),2);
        }

        return $online_users;
    }

    public static function getUsersByRoom($roomid)
    {
        $users = array_slice(scandir(self::$instance->online_dir.$roomid.'/'), 2);
        return $users;
    }

    public static function getUsers($roomid,$users)
    {
        $ret=[];
        foreach($users as $v){
            $ret[]=self::getUser($roomid,$v);
        }
        return $ret;
    }

    public static function getUser($roomid,$userid)
    {
        if($roomid==''){
            global $rooms;
            foreach($rooms as $k=>$v){
                if(file_exists(self::$instance->online_dir.$k.DIRECTORY_SEPARATOR.$userid)){
                    $roomid=$k;
                    break;
                }
            }
        }
        if(!is_file(self::$instance->online_dir.$roomid.DIRECTORY_SEPARATOR.$userid)){
            return false;
        }
        $ret = @file_get_contents(self::$instance->online_dir.$roomid.'/'.$userid);
        $info = @unserialize($ret);
        $info['roomid'] = $roomid;//赋予用户所在的房间
        return $info;
    }

    public static function logout($userid) {
        global $rooms;
        foreach($rooms as $_k => $_v){
            if(self::exists($_k,$userid)){
                unlink(self::$instance->online_dir.$_k.'/'.$userid);
                break;
            }
        }

    }

    public static function exists($roomid,$userid){
        if(file_exists(self::$instance->online_dir.$roomid.'/'.$userid)){
            return is_file(self::$instance->online_dir.$roomid.'/'.$userid);
        }
        return false;
    }
}