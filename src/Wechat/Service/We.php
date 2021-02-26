<?php
/*
 * 微信公众号认证相关通用方法
 *
 * WeAuth.php
 * 2019-04-17 16:41  guiyj<guiyj007@gmail.com>
 *
 */

namespace EasyUtils\Wechat\Service;

use EasyUtils\Kernel\constant\WeixinConst;
use EasyUtils\Kernel\constant\WxTemplateType;
use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\message\Service\Producer;
use EasyUtils\User\Service\UserFacade;

/**
 *  微信公众号认证相关通用方法
 */
class We
{
    /**
     * 公众号模板消息类型，与数据库表mp_template字段type一致
     *  1 openid, 2 uid, 3 reader_id
     */
    const MSG_TOUSER_TYPE_OPENID = 1;
    const MSG_TOUSER_TYPE_UID = 2;
    const MSG_TOUSER_TYPE_READER_ID = 3;

    /**
     * 发送消息队列形式的模板消息
     * @param int $aid 图书馆aid
     * @param string $tpl_type 模板消息类型，对应管理后台的消息类型，请用如下方式获取：EasyUtils\Kernel\constant\WxTemplateType
     * @param array $data 消息主体,eg: [
     *      'first' => ['value'=>'你好'],
     *      'keyword1' => ['value'=>'你好'],
     *      'remark' => ['value'=>'你好']
     *    ]
     * @param mixed $to_user            接收人唯一标识。可为用户uid，openid，读者证号。具体通过$touser_type来指定
     * @param int $touser_type          接收人唯一标识类型（1 openid, 2 uid, 3 reader_id），建议用如下方式设置：We::MSG_TOUSER_TYPE_OPENID
     * @param string $url               消息点击链接
     * @param int $batch_id             队列批次号，建议用于当前时间相关参数
     * @param string $miniapp_name      所需跳转到的小程序英文标志符。布狗图书馆传值： wxlib，布狗阅读传值:read
     * @param string $miniapp_pagepath  所需跳转到小程序的具体页面路径，支持带参数,（示例index?foo=bar）
     * @return bool             是否加入到消息队列
     */
    public static function queueTemplateMessage(
        $aid,
        $tpl_type,
        $data,
        $to_user,
        $touser_type = 1,
        $url = '',
        $batch_id = 0,
        $miniapp_name = '',
        $miniapp_pagepath = ''
    ) {
        $subject = 'we_message';
        $biz_id = $to_user;
        $biz_param = [
            '图书馆ID' => $aid,
            '模板类型' => $tpl_type,
            '接收人' => $to_user,
            '接收人类型' => $touser_type,
        ];

        $producer = new Producer(
            '',  //使用默认设置的回调，回调到sendTemplateMessage
            func_get_args(),
            $subject,
            $biz_id,
            $biz_param,
            $hold_day = 30,
            $batch_id
        );

        return $producer->sendMessage();
    }

    /**
     * sendTemplateMessage  发送模板消息
     * $id_type : 1 openid, 2 uid, 3 reader_id
     * $reply=[
     *  'template_id'=>'LsIc21raK3kWuX8j8hgBwJ-1cWn35PIdxgz2KMxgMPQ',
     *  'url'=>'http://baidu.com',
     *  'data'=>[
     *      'first' => ['value'=>'你好'],
     *      'keyword1' => ['value'=>'你好'],
     *      'remark' => ['value'=>'你好']
     *    ]
     * ];
     */
    public static function queueTemplateMessageCallback(
        $aid,
        $tpl_type,
        $data,
        $to_user,
        $touser_type = 1,
        $url = '',
        $batch_id = 0,
        $miniapp_name = '',
        $miniapp_pagepath = ''
    ) {
//        $ret_errors = [];
//        if (rand(1,10) > 1) {
//            return true;
//        } else {
//            biz_exception('debug_error');
//        }

        $ret_errors = self::sendTemplateMessage($aid, $tpl_type, $data, $to_user, $touser_type, $url, $miniapp_name, $miniapp_pagepath);
        if (count($ret_errors) > 0) {
            biz_exception($ret_errors[0]);
        }
        return true;
    }

    /**
     * 发送模板消息
     * @param int $aid 图书馆aid
     * @param string $tpl_type 模板消息类型，对应管理后台的消息类型，请用如下方式获取：EasyUtils\Kernel\constant\WxTemplateType
     * @param array $data 消息主体,eg: [
     *      'first' => ['value'=>'你好'],
     *      'keyword1' => ['value'=>'你好'],
     *      'remark' => ['value'=>'你好']
     *    ]
     * @param mixed $to_user            接收人唯一标识。可为用户uid，openid，读者证号。具体通过$touser_type来指定
     * @param int $touser_type          接收人唯一标识类型（1 openid, 2 uid, 3 reader_id），建议用如下方式设置：We::MSG_TOUSER_TYPE_OPENID
     * @param string $url               消息点击链接
     * @param string $miniapp_name      所需跳转到的小程序英文标志符。布狗图书馆传值： wxlib，布狗阅读传值:read
     * @param string $miniapp_pagepath  所需跳转到小程序的具体页面路径，支持带参数,（示例index?foo=bar）
     * @return array                    出错消息数组（全部成功时，返回空数组）
     * @throws \EasyUtils\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public static function sendTemplateMessage(
        $aid,
        $tpl_type,
        $data,
        $to_user,
        $touser_type = We::MSG_TOUSER_TYPE_OPENID,
        $url = '',
        $miniapp_name = '',
        $miniapp_pagepath = ''
    ) {
        /**
         * 1 获取该馆该类型的消息模板ID
         */
        $template = self::getMpTemplateConf($aid, $tpl_type, $is_group = 0);
        if (empty($template['template_id'])) {
            biz_exception('模板类型错误');
        }
        $template_id = $template['template_id'];// 微信模板消息ID;

        /**
         * 2 找到openid
         */
        if (self::MSG_TOUSER_TYPE_OPENID == $touser_type) {
            $openid_list = is_array($to_user) ? $to_user : [$to_user];
        } elseif (self::MSG_TOUSER_TYPE_UID == $touser_type) {
            //根据userid找openid（多个）
            $openid_list = UserFacade::getOpenIdByAidUid($aid, $to_user);
        } elseif (self::MSG_TOUSER_TYPE_READER_ID == $touser_type) {
            //根据reader_id 找openid（多个）
            $openid_list = UserFacade::getOpenIdByAidReaderId($aid, $to_user);
        }

        //非生产环境，可以采用env debug模式，发给debug接收者。 或通过GET参数wx_debug控制
        //一般不使用，建议在调用本方法外层，根据业务debug参数来获取openid传入
        //（获取方式：We::getDebugUsers($aid)）
        if (('product' != env_get('app_env') && env_get('wx_message_debug'))
            || !empty($_GET['wx_debug'])
        ) {
            $openid_list = self::getDebugUsers($aid);
        }

        if (empty($openid_list)) {
            biz_exception("openid为空");
        }

        //发送参数
        $handler = self::initEasyWechat($aid)->template_message;
        $tpl_msg = [
            'template_id' => $template_id,
            'url' => $url,
            'data' => $data,
        ];
        if ($miniapp_name && $miniapp_pagepath) {
            load_common_conf('wxapps');
            $tpl_msg['miniprogram'] = [
                'appid' => config("wxapps.{$miniapp_name}")['appid'],
                'pagepath' => $miniapp_pagepath
            ];
        }

        /**
         * 3 列表循环发送，记录返回错误消息
         */
        $ret_errors = [];
        foreach ($openid_list as $openid) {
            $tpl_msg['touser'] = $openid;
            $response = $handler->send($tpl_msg);
            if (!empty($response['errcode'])) {
                $ret_errors[] = $openid . ':' . $response['errmsg'];
//                . ':' . var_export($tpl_msg, true);
            }
        }
        return $ret_errors;
    }

    /**
     * 获取图书馆的debug用户列表
     * @param $aid
     * @param int $touser_type
     * @return []
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public static function getDebugUsers($aid, $touser_type = We::MSG_TOUSER_TYPE_OPENID)
    {
        load_common_conf('we_debug');
        $debug_info = config("we_debug.lib_{$aid}");
        if (empty($debug_info)) {
            biz_exception('无该馆的debug配置');
        }
        return We::MSG_TOUSER_TYPE_OPENID == $touser_type ? $debug_info['openids'] : $debug_info['reader_ids'];
    }

    /**
     * 发送模板消息
     * @param array $data
     * @return bool
     * @throws \EasyUtils\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public static function templateMessage($data) {
        //发送参数
        $handler = HandlerFactory::easyWechat(HandlerFactory::OFFICIAL_ACCOUNTS, $data['aid'])->template_message;
        $tpl_msg = [
            'template_id' => $data['template_id'],
            'url' => $data['url'],
            'miniprogram' =>$data['miniprogram'],
            'data' => $data['content'],
            'touser' => $data['openid']
        ];

        $response = $handler->send($tpl_msg);
        if (!isset($response['errcode'])) {
            biz_exception($data['openid'] . ":未知异常");
        } elseif ($response['errcode'] != 0) {
            biz_exception($data['openid'] . ':' . $response['errcode'] . '-' .$response['errmsg']);
        }
        return true;
    }

    /**
     * 获取图书馆微信消息模板配置信息
     * @param $aid
     * @param $type
     * @param int $is_group
     * @param array $cacheOpt
     * @return false|mixed|string
     * @throws \Exception
     */
    public static function getMpTemplateConf($aid, $type)
    {
        $tpl_list = self::getMpTemplatesByAid($aid);
        $tpl_list = array_column($tpl_list, null, 'type');
        return isset($tpl_list[$type]) ? $tpl_list[$type] : '';
//        return MpTemplate::get(['aid' => $aid, 'type' => $type, 'is_group' => $is_group]);
    }

    public static function getMpTemplatesByAid($aid, $cacheOpt = ['_cacheTime' => 180])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
        }
        $res = req_wxlib_v2('weixin/we.general/mpTemplates', ['aid' => $aid]);
        return $res['data'];
    }

    /**
     * 发送客服消息
     * 可以直接发送一个机器人功能id
     * 可以原生数组方式发送
     * @return boolean true-成功，false-失败
     * @author $this <498944516@qq.com>
     */
    public static function sendCustomMessage($to, $answer)
    {
    }

    /**
     * sendGroupMassMessage  群发消息
     * @author:$this 498944516@qq.com
     * $reply=[
     * filter'=>["is_to_all"=>False,  "group_id"=>"2" ],
     * mpnews | voice | image | mpvideo => array( "media_id"=>"MediaId")
     * text => array ( "content" => "hello")
     */
    public static function sendBroadcastingMessage($appid, $reply)
    {
    }

    //创建临时二维码
    public static function tempQrcode($aid, $url, $expire = 518400) //6 * 24 * 3600
    {
        $handler = self::initEasyWechat($aid);
        $result = $handler->qrcode->temporary($url, $expire);
        $url = $handler->qrcode->url($result['ticket']);    //获取二维码网址
        return $url;
    }

    //创建永久二维码
    public static function foreverQrcode($aid, $sceneValue)
    {
        $handler = self::initEasyWechat($aid);
        $result = $handler->qrcode->forever($sceneValue);
        $url = $handler->qrcode->url($result['ticket']);    //获取二维码网址
        return $url;
    }

    //长链接转短链接
    public static function shortenUrl($aid, $long_url)
    {
        $short_url = self::initEasyWechat($aid)->url->shorten($long_url);
        return empty($short_url['short_url']) ? '' : $short_url['short_url'];
    }

    /**
     * 获取临时素材内容
     * @param $aid
     * @param $mediaId
     * @return \Psr\Http\Message\StreamInterface
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public static function getMedia($aid, $mediaId)
    {
        $stream = self::initEasyWechat($aid)->media->get($mediaId);
        return $stream->getBody();
    }

    /**
     * 获取永久素材内容
     * @param $aid
     * @param $mediaId
     * @return mixed
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     * @example
     * {
     *   "news_item": [
     *   {
     *       "title":TITLE,
     *       "thumb_media_id"::THUMB_MEDIA_ID,
     *       "show_cover_pic":SHOW_COVER_PIC(0/1),
     *       "author":AUTHOR,
     *       "digest":DIGEST,
     *       "content":CONTENT,
     *       "url":URL,
     *       "content_source_url":CONTENT_SOURCE_URL
     *   },
     *   //多图文消息有多篇文章
     *   ]
     *   }
     */
    public static function getMaterial($aid, $mediaId)
    {
        $stream = self::initEasyWechat($aid)->material->get($mediaId);
        return $stream;
    }

    /**
     * 永久素材列表
     *
     * example:
     * {
     *   "total_count": TOTAL_COUNT,
     *   "item_count": ITEM_COUNT,
     *   "item": [{
     *             "media_id": MEDIA_ID,
     *             "name": NAME,
     *             "update_time": UPDATE_TIME
     *         },
     *         // more...
     *   ]
     * }
     */
    public static function listMaterial($aid, $type, $offset = 0, $count = 20)
    {
        $stream = self::initEasyWechat($aid)->material->list($type, $offset, $count);
        return $stream;
    }

    /**
     * 获取JSSDK的配置数组
     * 默认返回 JSON 字符串，当 $json 为 false 时返回数组，你可以直接使用到网页中。
     *
     * @param $aid
     * @param string $url
     * @return mixed
     * @example
     * {
     *   debug: true,
     *   appId: 'wx3cf0f39249eb0e60',
     *   timestamp: 1430009304,
     *   nonceStr: 'qey94m021ik',
     *   signature: '4F76593A4245644FAE4E1BC940F6422A0C3EC03E',
     *   jsApiList: ['updateAppMessageShareData', 'updateTimelineShareData']
     *   }
     */
    public static function jssdkConfig($aid, $url = '', $jsApiList=[], $debug=false)
    {
        $jsApiList = $jsApiList ?: array('onMenuShareQQ', 'onMenuShareWeibo');
        $config    = self::initEasyWechat($aid)->jssdk;
        $url && $config = $config->setUrl($url);
        $config = $config->buildConfig($jsApiList, $debug, $beta = false, $json = true);
        return $config;
    }

    /**
     * 读取（查询）已设置菜单
     *
     * example:
     * {
     *   "menu": {
     *      "button": [
     *      {
     *          "type": "click",
     *          "name": "今日歌曲",
     *          "key": "V1001_TODAY_MUSIC",
     *          "sub_button": [ ]
     *      },
     *      ]
     *   }
     * }
     */
    public static function listMenu($aid)
    {
        $stream = self::initEasyWechat($aid)->menu->list();
        return $stream;
    }

    /**
     * Get current menus.
     * @param $aid
     * @return array
     */
    public static function listCurrentMenu($aid)
    {
        $stream = self::initEasyWechat($aid)->menu->current();
        return ['mene'=>$stream['selfmenu_info']];
    }

    /**
     * 添加菜单
     * @param $aid
     * @param array $buttons 菜单数组
     * @param array $matchRule 个性化菜单数组
     * @return mixed
     * example:
     * {
     *  "button":[
     *   {
     *       "type":"click",
     *       "name":"今日歌曲",
     *       "key":"V1001_TODAY_MUSIC"
     *   },
     *  ]
     *  }
     */
    public static function createMenu($aid, $buttons, $matchRule = [])
    {
        $stream = self::initEasyWechat($aid)->menu->create($buttons, $matchRule);
        return $stream;
    }

    /**
     * 删除菜单
     * @param $aid
     * @param $menuId 个性化菜单时用，ID 从查询接口获取
     */
    public static function deleteMenu($aid, $menuId = '')
    {
        $stream = self::initEasyWechat($aid)->menu->delete($menuId);
        return $stream;
    }


    /**
     * 获取公众号用户
     * @param $aid
     * @param $nextOpenId 第一个拉取的OPENID，不填默认从头开始拉取
     * @return mixed
     * example:
     * {
     *  "total": 2,
     *  "count": 2,
     *  "data": {
     *  "openid": [
     *      "OPENID1",
     *      "OPENID2"
     *  ]
     * },
     *  "next_openid": "NEXT_OPENID"
     * }
     */
    public static function listUser($aid, $nextOpenId = null)
    {
        $stream = self::initEasyWechat($aid)->user->list($nextOpenId);
        return $stream;
    }

    /**
     * 获取公众号用户信息
     * @param $aid
     * @return mixed
     */
    public static function selectUser($aid, $arrOpenId)
    {
        $stream = self::initEasyWechat($aid)->user->select($arrOpenId);
        return $stream;
    }

    /**
     * 获取单个用户信息
     * @param $aid
     * @return mixed
     */
    public static function getUser($aid, $openId)
    {
        $stream = self::initEasyWechat($aid)->user->get($openId);
        return $stream;
    }

    /**
     * 添加模板
     * @param int $aid
     * @param string $shortId 在公众号后台获取
     * @return mixed
     */
    public static function addTemplate($aid, $shortId)
    {
        $stream = self::initEasyWechat($aid)->template_message->addTemplate($shortId);
        return $stream;
    }

    /**
     * 获取所有模板列表
     * @param int $aid
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public static function getPrivateTemplates($aid)
    {
        $stream = self::initEasyWechat($aid)->template_message->getPrivateTemplates();
        return $stream;
    }
    
    public static function initEasyWechat($aid)
    {
        return HandlerFactory::easyWechat(HandlerFactory::OFFICIAL_ACCOUNTS, $aid);
    }
}
