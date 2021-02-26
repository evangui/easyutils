<?php
/**
 * Common.php
 * 2020-02-11  wangpeng<wangpeng@bookgoal.com.cn>
 */
namespace EasyUtils\MessageSender\Service;

use app\message\model\ShortMessage;
use EasyUtils\Apibase\RpcFactory;
use EasyUtils\MessageSender\model\MessageRecord;
use EasyUtils\MessageSender\model\MessageStatistics;
use EasyUtils\MessageSender\model\SmAuth;
use EasyUtils\User\model\Readers;
use EasyUtils\User\model\ReadersExtView;
use EasyUtils\User\model\UsersExt;
use EasyUtils\User\model\UsersLibfans;
use EasyUtils\User\Service\UserFacade;
use EasyUtils\Wechat\Service\OpenWe;
use EasyUtils\Wechat\Service\Wxapp;

class Message
{
    /**
     * 用户类型类型
     *  1 openid, 2 uid, 3 reader_id
     */
    const MSG_TOUSER_TYPE_OPENID    = 1;
    const MSG_TOUSER_TYPE_UID       = 2;
    const MSG_TOUSER_TYPE_READER_ID = 3;

    protected static $self;

    /**
     * @return Message
     */
    public static function getInstance()
    {
        if (!self::$self) {
            $called_class = get_called_class();
            self::$self = new $called_class();
        }
        return self::$self;
    }

    /**
     * 发送消息
     * @param int $message_type
     * @param MessageData $message_data
     * @param string $user
     * @param int $user_type
     * @param array $path_data
     * @param int $replace_type
     * @return int
     */
    public function send($message_type, MessageData $message_data, $user, $user_type, $path_data, $replace_type = 0, $to_uid=0)
    {
        $record = [
            'message_type' => $message_type,
            'aid' => $path_data['aid'],
            'type' => 1,
            'user' => $user,
            'user_type' => $user_type
        ];

        if (!$to_uid) {
            if ($user_type == Message::MSG_TOUSER_TYPE_UID) {
                $to_uid = $user;
            } else if ($user_type == Message::MSG_TOUSER_TYPE_READER_ID) {
                //根据读者证号找uid
                $to_uid = Readers::alias('a')->join('buc_wxapp_readers b', 'a.id=b.readers_id')
                    ->where(['reader_id' => $user])->column('uid');
//                $to_uid = ReadersExtView::where(['reader_id' => $user])->column('uid');
            }
        }
        is_array($to_uid) && $to_uid = array_unique($to_uid);

        //debug
//        $result = $this->send_short_message($record, $message_data, $to_uid, 1);

        //判断是否有订阅消息
        $result = $this->send_subscribe_message($record, $message_data, $path_data, $replace_type);
        if ($result) {
            if (!$to_uid && $user_type == Message::MSG_TOUSER_TYPE_OPENID) {
                //根据小程序openid找uid
                $to_uid = UsersExt::where(['openid' => $user])->column('users_id');
            }
            if ($to_uid) {
                //发送站内消息
                $result = $this->send_short_message($record, $message_data, $to_uid, 1);
            }
            return $result;
        }

        //判断是否有模板消息
        $result = $this->send_template_message($record, $message_data, $path_data);
        if ($result) {
            if (!$to_uid && $user_type == Message::MSG_TOUSER_TYPE_OPENID) {
                //根据公众号openid找uid
                $to_uid = UsersLibfans::where(['openid' => $user])->column('uid');
            }
            if ($to_uid) {
                //发送站内消息
                $result = $this->send_short_message($record, $message_data, $to_uid, 2);
            }
            return $result;
        }
        //判断是否有短信消息
        //判断是否有邮件消息
        return 0;
    }

    /**
     * 发送站内消息
     * @param int $message_type
     * @param MessageData $message_data
     * @param string $user
     * @return int
     */
    public function send_short_message($record, MessageData $message_data, $to_uid, $send_type)
    {
        $msg = $this->get_short_msg($message_data, $record['message_type'], $send_type);
        if ($msg) {
            $time = time();
            $tpl_item = [
                'aid' => $record['aid'],
                'to_uid' => $to_uid,
                'to_msg' => $msg,
                'op_time' => $time,
                'create_time' => $time,
            ];
            $data = [];
            if (is_array($to_uid)) {
                //多个用户发送，适用于一个读者证绑定多个用户的情况
                foreach ($to_uid as $uid) {
                    $item = $tpl_item;
                    $item['to_uid'] = $uid;
                    $data[] = $item;
                }
            } else {
                $data = [$tpl_item];
            }
            $res = ShortMessage::insertAll($data);
            return $res;
        }
        return false;
    }

    /**
     * @param MessageData $data
     * @param int $message_type
     * @param int $send_type
     * @return array
     */
    public function get_content(MessageData $data, $message_type, $send_type)
    {
        $data = (array)$data;
        $content = config('send_message.'.$message_type.'.'.$send_type.'.content');
        if (!empty($content)) {
            foreach ($content as $key => $val) {
                $content[$key] = $data[$val] ?? '';
            }
        }
        return $content;
    }

    /**
     * 将微信模板消息的内容转换成站内短消息
     * @param MessageData $data
     * @param int $message_type
     * @param int $send_type
     * @return array
     */
    public function get_short_msg(MessageData $data, $message_type, $send_type)
    {
        $short_msg = config('send_message.'.$message_type.'.'.$send_type.'.short_msg');
        if (!$short_msg) {
            return '';
        }

        $data = (array)$data;
        $content = config('send_message.'.$message_type.'.'.$send_type.'.content');
        if (!empty($content)) {
            foreach ($content as $val) {
                $s = is_array($data[$val]) ? current($data[$val]) : $data[$val];
                $short_msg = str_replace('${' . $val . '}', $s, $short_msg);
            }
        }
        return $short_msg;
    }

    /**
     * @param array $path_data
     * @param int $message_type
     * @param int $send_type
     * @return array|string
     */
    public function get_page_path($path_data, $message_type, $send_type)
    {
        $url = config('send_message.'.$message_type.'.'.$send_type.'.link.url');
        if (!empty($url)) {
            foreach ($path_data as $key => $val) {
                if ($key == 'wxapp_name') {
                    continue;
                }
                $url = str_replace("{{".$key."}}", $val, $url);
            }
            return $url;
        }
        $miniprogram = config('send_message.'.$message_type.'.'.$send_type.'.link.miniprogram');
        if (!empty($miniprogram) && is_array($miniprogram)) {
            $miniprogram['appid'] = config('wxapp.'.$path_data['wxapp_name'].'.appid');
            foreach ($path_data as $key => $val) {
                if ($key == 'wxapp_name') {
                    continue;
                }
                $miniprogram['pagepath'] = str_replace("{{".$key."}}", $val, $miniprogram['pagepath']);
            }
            return $miniprogram;
        }
        return '';
    }

    /**
     * @param array $record
     * @param MessageData $message_data
     * @param array $path_data
     * @param int $replace_type
     * @return int
     */
    public function send_subscribe_message($record, MessageData $message_data, $path_data, $replace_type = 0)
    {
        $openid_list = [];
        switch ($record['user_type']) {
            case self::MSG_TOUSER_TYPE_OPENID :
                $openid_list = is_array($record['user']) ? $record['user'] : [$record['user']];
                break;
            case  self::MSG_TOUSER_TYPE_UID :
                //根据userid找openid（多个）
                try {
                    $openid_list = RpcFactory::user()->user3rd->getBgOpenidByUid($record['user'], 1, $path_data['wxapp_name']);
                } catch (\Exception $e) {
                    return 0;
                }
                break;
            case self::MSG_TOUSER_TYPE_READER_ID :
                //根据reader_id 找openid（多个）
                try {
                    $openid_list = RpcFactory::user()->user3rd->getOpenIdByAidReaderId($record['aid'], $record['user'], 1);
                } catch (\Exception $e) {
                    return 0;
                }
                break;
        }
        if (empty($openid_list)) {
            return 0;
        }
        try {
            $template = Wxapp::getMiniTemplate($record['message_type'], $path_data['wxapp_name']);
        } catch (\Exception $e) {
            return 0;
        }
        if ($template) {
            $message_type = $replace_type ? : $record['message_type'];
            try {
                $auth_list = RpcFactory::user()->user3rd->getSmAuth($record['aid'], $template['template_id'],
                    $message_type, $openid_list, $path_data['wxapp_name']);
            } catch (\Exception $e) {
                return 0;
            }
            if ($auth_list) {
                $record['type'] = 1;
                $content = $this->get_content($message_data, $record['message_type'], $record['type']);
                if (!empty($content)) {
                    $data = [
                        'wxapp_name' => $path_data['wxapp_name'],
                        'aid' => $record['aid'],
                        'content' => $content,
                        'template_id' => $template['template_id'],
                        'openid' => '',
                        'url' => '',
                    ];
                    $result = 0;
                    foreach ($auth_list as $auth_id => $openid) {
                        $data['openid'] = $openid;
                        $record['user'] = $openid;
                        if ($this->send_message($data, $record, $path_data, $auth_id)) {
                            $result++;
                        }
                    }
                    return $result;
                }
            }
        }
        return 0;
    }

    /**
     * @param array $record
     * @param MessageData $message_data
     * @param array $openid_list
     * @param array $path_data
     * @return int
     */
    public function send_template_message($record, MessageData $message_data, $path_data)
    {
        $openid_list = [];
        switch ($record['user_type']) {
            case self::MSG_TOUSER_TYPE_OPENID :
                $openid_list = is_array($record['user']) ? $record['user'] : [$record['user']];
                break;
            case self::MSG_TOUSER_TYPE_UID :
                //根据userid找openid（多个）
                try {
                    $openid_list = RpcFactory::user()->user3rd->getOpenIdByAidUid($record['aid'], $record['user']);
                } catch (\Exception $e) {
                    return 0;
                }
                break;
            case self::MSG_TOUSER_TYPE_READER_ID :
                //根据reader_id 找openid（多个）
                try {
                    $openid_list = RpcFactory::user()->user3rd->getOpenIdByAidReaderId($record['aid'], $record['user'], 2);
                } catch (\Exception $e) {
                    return 0;
                }
                break;
        }

//        //非生产环境，可以采用env debug模式，发给debug接收者。 或通过GET参数wx_debug控制
//        //一般不使用，建议在调用本方法外层，根据业务debug参数来获取openid传入
//        //（获取方式：We::getDebugUsers($aid)）
//        if (('product' != env_get('app_env') && env_get('wx_message_debug'))
//            || !empty($_GET['wx_debug'])
//        ) {
//            $openid_list = OpenWe::getDebugUsers($record['aid']);
//        }
        if (empty($openid_list)) {
            return 0;
        }
        try {
            $template = OpenWe::getMpTemplateConf($record['aid'], $record['message_type']);
        } catch (\Exception $e) {
            return 0;
        }
        if ($template) {
            $record['type'] = 2;
            $content = $this->get_content($message_data, $record['message_type'], $record['type']);
            if (!empty($content)) {
                $data = [
                    'aid' => $record['aid'],
                    'content' => $content,
                    'template_id' => $template['template_id'],
                    'openid' => '',
                    'url' => '',
                    'miniprogram' => ''
                ];
                $result = 0;
                foreach ($openid_list as $openid) {
                    $data['openid'] = $openid;
                    $record['user'] = $openid;
                    if ($this->send_message($data, $record, $path_data)) {
                        $result++;
                    }
                }
                return $result;
            }
        }
        return 0;
    }

    /**
     * @param array $data
     * @param array $record
     * @param array $path_data
     * @param int $auth_id
     * @return bool
     */
    public function send_message($data, $record, $path_data, $auth_id = 0)
    {
        try {
            $mid = RpcFactory::user()->user3rd->getMid($record['aid'], $record['message_type'], $record['type'],
                $record['user'], $record['user_type']);
        } catch (\Exception $e) {
            return false;
        }
        $path_data['mid'] = $mid;
        $page_path = $this->get_page_path($path_data, $record['message_type'], $record['type']);
        if (is_array($page_path)) {
            $data['miniprogram'] = $page_path;
        } else {
            $data['url'] = $page_path;
        }
        if (!MessageFactory::getRelay($record['type'])->send($data)) {
            return false;
        }
        RpcFactory::user()->user3rd->statMessage($record['message_type'], $auth_id);
        return true;
    }
}