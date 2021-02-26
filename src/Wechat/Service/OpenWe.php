<?php
/*
 * 微信公众号认证相关通用方法
 *
 * WeAuth.php
 * 2019-04-17 16:41  guiyj<guiyj007@gmail.com>
 *
 */

namespace EasyUtils\Wechat\Service;

use EasyUtils\Kernel\Constant\WxTemplateType;
use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\message\Service\Producer;
use EasyUtils\User\Service\UserFacade;

/**
 *  微信公众号认证相关通用方法
 */
class OpenWe extends We
{
    /**
     * 公众号模板消息类型，与数据库表mp_template字段type一致
     *  1 openid, 2 uid, 3 reader_id
     */
    const MSG_TOUSER_TYPE_OPENID = 1;
    const MSG_TOUSER_TYPE_UID = 2;
    const MSG_TOUSER_TYPE_READER_ID = 3;

    /**
     * 获取关联的小程序列表
     * @param $aid
     * @return mixed
     */
    public static function listMiniProgram($aid)
    {
        $stream = self::initEasyWechat($aid)->mini_program->list();
        return $stream;
    }

    /**
     * 添加关联小程序
     * @param $aid
     * @param $appId
     * @param bool $notifyUsers
     * @param bool $showProfile
     * @return mixed
     */
    public static function addMiniProgram($aid, $appId, $notifyUsers = false, $showProfile = false)
    {
        $stream = self::initEasyWechat($aid)->mini_program->link($appId, $notifyUsers, $showProfile);
        return $stream;
    }

    /**
     * 解除已关联的小程序.
     * @param $aid
     * @param $appId
     * @return mixed
     */
    public static function delMiniProgram($aid, $appId)
    {
        $stream = self::initEasyWechat($aid)->mini_program->unlink($appId);
        return $stream;
    }

    /**
     * @param $aid
     * @return \EasyUtils\MicroMerchant\Application|\EasyUtils\MiniProgram\Application|\EasyUtils\OfficialAccount\Application|\EasyUtils\OpenPlatform\Authorizer\MiniProgram\Application|\EasyUtils\OpenPlatform\Authorizer\OfficialAccount\Application|\EasyUtils\Payment\Application
     */
    public static function initEasyWechat($aid)
    {
        return HandlerFactory::easyWechat(HandlerFactory::OPEN_PLATRORM_WE, $aid);
    }
}
