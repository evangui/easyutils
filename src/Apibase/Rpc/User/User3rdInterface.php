<?php
/*
 * 第三方平台用户 服务方法接口
 *
 * User3rdInterface.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * 用于rpc服务类，rpc客户端调用方法类的接口定义
 */
namespace EasyUtils\Apibase\Rpc\User;


/**
 * 第三方平台用户 服务方法接口
 */
interface User3rdInterface
{
    /**
     * 获取馆用户信息
     * @param int $aid
     * @param int $uid
     * @return []
     */
    public function getLibUser($aid, $uid);

    /**
     * 修改馆用户积分
     * @param int $readers_id
     * @param string $point
     * @return []
     */
    public function updateLibUserPoint($aid, $uid, $point);

    /**
     * 根据图书馆编号和uid 获取用户微信公众号的多个openid
     * @param integer $aid 图书馆编号
     * @param string $readerId 读者证号
     * @return array openid列表
     */
    public function getOpenIdByAidUid($aid, $uid);

    /**
     * 根据uid 获取 布狗小程序或公众号用户的openid，数组形式
     * @param integer $aid 图书馆编号
     * @param string $fans_type 粉丝类型,如1 小程序粉丝，2 公众号粉丝
     * @return array openid列表
     */
    public function getBgOpenidByUid($uids, $fans_type, $app_name='');

    /**
     * 根据图书馆编号和读者证号获取读者证绑定用户的多个openid
     * @param integer $aid 图书馆编号
     * @param string $readerId 读者证号
     * @return array openid列表
     */
    public function getOpenIdByAidReaderId($aid, $readerId, $fans_type);

    /**
     * 根据app名称与类型，获取对应appid
     * @param string $app_name
     * @param int $app_type
     * @return string
     */
    public function getAppidByAppname($app_name, $app_type);

    /**
     * 获取订阅消息的授权信息
     * @param int $aid
     * @param string $template_id
     * @param int $message_type
     * @param array $openid_list
     * @param string $app_name
     * @return array
     */
    public function getSmAuth($aid, $template_id, $message_type, $openid_list, $app_name='wxlib');

    /**
     * 获取消息推送记录ID
     * @param int $aid
     * @param int $message_type
     * @param int $type
     * @param string $user
     * @param int $user_type
     * @return int
     */
    public function getMid($aid, $message_type, $type, $user, $user_type);

    /**
     * 消息推送统计
     * @param int $message_type
     * @param int $auth_id
     * @return bool
     */
    public function statMessage($message_type, $auth_id=0);
}

