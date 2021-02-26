<?php
/*
 * 博库微信功能助手函数
 *
 * we_helper.php
 */
use EasyUtils\Wechat\Service\WeAuth;
use EasyUtils\Wechat\Service\We;

/**
 * 获取图书馆的通用微信配置信息
 * @param $aid
 * @param array $cacheOpt
 * @return mixed
 */
function we_conf($aid, $cacheOpt = ['_cacheTime'=> 86400])
{
    return \EasyUtils\Kernel\Support\LibConf::weConf($aid, $cacheOpt);
}

/**
 * 根据aid让微信用户针对该公众号授权，并获取用户信息
 *
 * @param string $appid
 * @param string $scope
 * @return array
 * @example
 * [
 *   'openid'     => 'oWehLw9LSU20Hebr_vK-ZBdBSCa0',
 *   'nickname'   => 'test_login',
 *   'sex'        => 1,
 *   'location'   => '-',
 *   'headimgurl' => 'https://avatar-static.segmentfault.com/418/273/4182733107-5aed136d3cffd_big64',
 *   'unionid'    => 'oaGwMw0noWGC_hAXLbY4H76CDZj0'
 * ]
 */
function we_auth($aid, $scope="snsapi_base")
{
    return (new WeAuth($aid, $scope, true))->getUserinfo();
}

/**
 * 根据appid获取当前在线用的openid
 * @param string $appid
 * @return string
 */
function we_get_openid($aid)
{
    return (new WeAuth($aid, '', false))->getOpenid();
}

//老版本sdk获取微信用户信息，不建议用
function we_auth_old($appid, $scope="snsapi_base")
{
    $options = model('weixin/We')->info($appid);
    $auth = new \weixin\sdk\Auth($options, $scope);
    return $auth->wxuser;
}

//老版本sdk初始化
function we_init($appid) {
    $options = model('weixin/We')->info($appid);
    $weObj = new \weixin\sdk\Wechat($options);
    $weObj->options = $options;
    return $weObj;
}

/**
 * 发送公众号模板消息
 * @param int $aid  图书馆aid
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
function we_send_message(
    $aid,
    $tpl_type,
    $data,
    $to_user,
    $touser_type=We::MSG_TOUSER_TYPE_OPENID,
    $url = '',
    $miniapp_name = '',
    $miniapp_pagepath = ''
) {
    return We::sendTemplateMessage($aid, $tpl_type, $data, $to_user, $touser_type, $url, $miniapp_name, $miniapp_pagepath);
}

/**
 * 发送消息队列形式的模板消息
 * @param int $aid  图书馆aid
 * @param string $tpl_type 模板消息类型，对应管理后台的消息类型，请用如下方式获取：EasyUtils\Kernel\constant\WxTemplateType
 * @param array $data 消息主体,eg: [
 *      'first' => ['value'=>'你好'],
 *      'keyword1' => ['value'=>'你好'],
 *      'remark' => ['value'=>'你好']
 *    ]
 * @param mixed $to_user    接收人唯一标识。可为用户uid，openid，读者证号。具体通过$touser_type来指定
 * @param int $touser_type  接收人唯一标识类型（1 openid, 2 uid, 3 reader_id），请用如下方式设置：We::MSG_TOUSER_TYPE_OPENID
 * @param string $url       消息点击链接
 * @param int $batch_id     队列批次号，建议用于当前时间相关参数
 * @return bool             是否加入到消息队列
 */
function we_queue_message($aid, $tpl_type, $data, $to_user, $touser_type=We::MSG_TOUSER_TYPE_OPENID, $url = '', $batch_id=0)
{
    return We::queueTemplateMessage($aid, $tpl_type, $data, $to_user, $touser_type, $url, $batch_id);
}