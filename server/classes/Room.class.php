<?php
/**
 * Created by PhpStorm.
 * User: yuanlj
 * Date: 2018/6/1
 * Time: 14:57
 */

class Room
{

    //房间ID与客户端连接ID对应关系 redis key
    public static $rfMap = 'rfMap';

    //正在线上的 redis key
    public static $onLine = 'onLine';

    //登录后的用户对应的user_id key
    public static $chatUser = 'chat_user';

    public static function getRedis()
    {
        global $redis_conf;
        return new Predis($redis_conf);
    }

    /**
     * 获取房间信息
     * @return array
     */
    public static function getRooms()
    {
        global $rooms;

        $roomArr = [];

        foreach ($rooms as $k => $v) {
            $roomArr[] = [
                'roomid' => $k,
                'roomname' => $v
            ];
        }

        return $roomArr;
    }

    /**
     * 登录
     * @param $userId 用户ID
     * @param $fd 客户端连接ID
     */
    public static function login($userId, $fd)
    {

        self::getRedis()->zAdd(self::$onLine, $userId, $fd);
    }

    /**
     * 通过客户端连接ID 获取 用户ID
     * @param $fd
     * @return float
     */
    public static function getUserId($fd)
    {
        return self::getRedis()->zScore(self::$onLine, $fd);
    }

    /**
     * 进入房间
     * @param $roomId 房间ID
     * @param $fd 客户端连接ID
     */
    public static function joinRoom($roomId, $fd)
    {
        $userId = self::getUserId($fd);
        self::getRedis()->zAdd(self::$rfMap, $roomId, $fd);
        self::getRedis()->hSet("room:{$roomId}", $fd, $userId);
    }

    /**
     * 通过用户userId获取其客户端连接ID
     * @param $userId
     * @return array|bool
     */
    public static function getUsersFd($userId)
    {
        return self::getRedis()->zRange(self::$onLine, $userId, $userId);
    }

    /**
     * 通过客户端连接ID 获取房间ID
     * @param $fd
     */
    public static function getRoomIdByFd($fd)
    {
        return self::getRedis()->zScore(self::$rfMap, $fd);
    }

    /**
     * 获取roomId中所有连接的客户端ID
     * @param $roomId
     * @return array|bool
     */
    public static function selectRoomFd($roomId)
    {
        return self::getRedis()->hKeys("room:{$roomId}");
    }

    /**
     * 退出房间
     * @param $roomId
     * @param $fd
     */
    public static function exitRoom($roomId, $fd)
    {
        self::getRedis()->hdel("room:{$roomId}", $fd);
        self::getRedis()->zRem(self::$rfMap, $fd);
    }

    /**
     * 关闭连接
     * @param  string $fd 链接id
     */
    public static function close($fd)
    {
        $roomId = self::getRoomIdByFd($fd);
        self::exitRoom($roomId, $fd);
        self::getRedis()->zRem(self::$onLine, $fd);
    }

    public static function open()
    {
        $pushMsg['code'] = 4;
        $pushMsg['msg'] = 'success';
        $pushMsg['data']['mine'] = 0;
        $pushMsg['data']['rooms'] = self::getRooms();
        $pushMsg['data']['users'] = self::getOnlineUsers();
        unset($data);
        return $pushMsg;
    }

    /**
     * 获取每个房间的在线客户端
     */
    public static function getOnlineUsers()
    {
        global $rooms;

        $arr = [];

        foreach ($rooms as $k => $v) {
//            $arr[] = [
//                'room_id' => $v,
//                'user_fd' => self::selectRoomFd($v),
//            ];
            //每个房间对应的用户信息
            $arr[$v] = self::getUsersByRoom($v);
        }

        return $arr;
    }

    //登录
    public static function doLogin($data)
    {
        $pushMsg['code'] = 1;
        $pushMsg['msg'] = $data['params']['name'] . "加入了群聊";

        $pushMsg['data']['roomid'] = $data['roomid'];
        $pushMsg['data']['fd'] = $data['fd'];
        $pushMsg['data']['name'] = $data['params']['name'];
        $pushMsg['data']['avatar'] = DOMAIN . '/static/images/avatar/f1/f_' . rand(1, 12) . '.jpg';
        $pushMsg['data']['time'] = date("H:i", time());

        //redis 中已经登录的用户key
        $user = self::getRedis()->hKeys(self::$chatUser);


        //将新登录的用户存入redis hash
        $userId = end($user) + 1;  //用户userId
        self::getRedis()->hSet(self::$chatUser, $userId, json_encode($pushMsg['data']));
        self::getRedis()->set($userId, $data['params']['name']);
        //加入房间
        self::joinRoom($data['roomid'], $data['fd']);
        //登录
        self::login($userId, $data['fd']);
        unset($data);

        return $pushMsg;
    }

    //发送新消息
    public static function sendNewMsg($data)
    {
        $pushMsg['code'] = 2;
        $pushMsg['msg'] = "";
        $pushMsg['data']['roomid'] = $data['roomid'];
        $pushMsg['data']['fd'] = $data['fd'];
        $pushMsg['data']['name'] = $data['params']['name'];
        $pushMsg['data']['avatar'] = $data['params']['avatar'];
        $pushMsg['data']['newmessage'] = escape(htmlspecialchars($data['message']));
        $pushMsg['data']['remains'] = array();
        if ($data['c'] == 'img') {
            $pushMsg['data']['newmessage'] = '<img class="chat-img" onclick="preview(this)" style="display: block; max-width: 120px; max-height: 120px; visibility: visible;" src=' . $pushMsg['data']['newmessage'] . '>';
        } else {
            global $emotion;
            foreach ($emotion as $_k => $_v) {
                $pushMsg['data']['newmessage'] = str_replace($_k, $_v, $pushMsg['data']['newmessage']);
            }
            $tmp = self::remind($data['roomid'], $pushMsg['data']['newmessage']);

            if ($tmp) {
                $pushMsg['data']['newmessage'] = $tmp['msg'];
                $pushMsg['data']['remains'] = $tmp['remains'];
            }
            unset($tmp);
        }
        $pushMsg['data']['time'] = date("H:i", time());
        unset($data);
        return $pushMsg;
    }

    public static function remind($roomid, $msg)
    {
        $data = array();
        if ($msg != "") {
            $data['msg'] = $msg;
            //正则匹配出所有@的人来
            $s = preg_match_all('~@(.+?)　~', $msg, $matches);
            if ($s) {
                $m1 = array_unique($matches[0]);
                $m2 = array_unique($matches[1]);

                $users = self::getUsersByRoom($roomid);

                $m3 = array();
                foreach ($users as $_k => $_v) {
                    $m3[$_v['name']] = $_v['fd'];
                }
                $i = 0;
                foreach ($m2 as $_k => $_v) {
                    if (array_key_exists($_v, $m3)) {
                        $data['msg'] = str_replace($m1[$_k], '<font color="blue">' . trim($m1[$_k]) . '</font>', $data['msg']);
                        $data['remains'][$i]['fd'] = $m3[$_v];
                        $data['remains'][$i]['name'] = $_v;
                        $i++;
                    }
                }
                unset($users);
                unset($m1, $m2, $m3);
            }
        }
        return $data;
    }

    /**
     * 获取指定房间里的用户信息
     * @param $roomId
     * @return array
     */
    public static function getUsersByRoom($roomId)
    {
        $lists = self::selectRoomFd($roomId);
        $arr = [];
        foreach ($lists as $k => $v) {
            $userId = self::getUserId($v);
            $userInfo = self::getRedis()->hGet(self::$chatUser, $userId);
            $arr[] = json_decode($userInfo, true);
        }
        return $arr;

    }

    /**
     * 客户端关闭连接,退出登录
     * @param $data
     * @return mixed
     */
    public static function doLogout($data)
    {
        //退出登录,删除相关redis信息
        self::logout($data['fd']);

        $pushMsg['code'] = 3;
        $pushMsg['msg'] = $data['params']['name'] . "退出了群聊";
        $pushMsg['data']['fd'] = $data['fd'];
        $pushMsg['data']['name'] = $data['params']['name'];
        unset($data);
        return $pushMsg;
    }

    /**
     * 退出登录需要清除的redis数据
     * @param $fd
     */
    public static function logout($fd)
    {
        $userId = self::getUserId($fd);
        //关闭连接
        self::close($fd);

        //从用户中删除
        self::getRedis()->hdel(self::$chatUser, $userId);
        self::getRedis()->del($userId);

    }

    /**
     * 更换房间
     * @param $data
     */
    public static function changeRoom($data)
    {
        $pushMsg['code'] = 6;
        $pushMsg['msg'] = '换房成功';

        $res = self::changeUser($data['oldroomid'], $data['fd'], $data['roomid']);
        if ($res) {
            $pushMsg['data']['oldroomid'] = $data['oldroomid'];
            $pushMsg['data']['roomid'] = $data['roomid'];
            $pushMsg['data']['mine'] = 0;
            $pushMsg['data']['fd'] = $data['fd'];
            $pushMsg['data']['name'] = $data['params']['name'];
            $pushMsg['data']['avatar'] = $data['params']['avatar'];
            $pushMsg['data']['time'] = date("H:i", time());
            unset($data);
            return $pushMsg;
        }
    }

    /**
     * 切换房间需要更改的redis数据
     * @param $oldRoomId
     * @param $fd
     * @param $newRoomId
     */
    public static function changeUser($oldRoomId, $fd, $newRoomId)
    {
        //退出老房间
        self::exitRoom($oldRoomId, $fd);
        //加入新房间
        self::joinRoom($newRoomId, $fd);

        return true;
    }

    /**
     * 通过客户端连接ID 获取用户的基本信息
     * @param $fd
     * @return mixed
     */
    public static function getUserInfoByFd($fd)
    {
        $userId = self::getUserId($fd);
        $userInfo = self::getRedis()->hGet(self::$chatUser, $userId);
        return json_decode($userInfo, true);
    }

}