<?php
/*
 * 博库用户 服务方法接口
 *
 * User3rdInterface.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * 用于rpc服务类，rpc客户端调用方法类的接口定义
 */
namespace EasyUtils\Apibase\Rpc\User;


/**
 * 博库用户 服务方法接口
 */
interface UserInterface
{
    /**
     * 博库的微信用户登录，同时生成access token
     * @param string unionid varchar(32)	bookgoal用户的unionid
     * @param int type tinyint(1)	用户类型，1:小程序 2:布狗学习服务号 3:官网
     * @param string from_appid varchar(32)	入口应用ID，与from_aid任选其一
     * @param int from_aid varchar(32)	入口应用在博库的编号，与from_appid任选其一
     * @param string from_openid varchar(32)	用户在入口应用中的openid，可选
     * @param string openid varchar(32)	不同类型bookgoal用户的openid
     * @param string nickname varchar(255)	用户昵称
     * @param string headimgurl varchar(255)	用户头像
     * @param int sex 可选 微信资料
     * @param string city 可选 微信资料
     * @param string country 可选 微信资料
     * @param string province 可选 微信资料
     *
     * @return
     * array (
     *    'uid' => 69459,
     *    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI11Y',
     *    'from_appid' => 'wx0457a3c4233e83f8',
     *    'from_aid' => '10',
     *    'library' => '中南民族大学图书馆',
     *    'headimgurl' => 'aaa',
     *    'lib_mp_auth' => 0,
     * )
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function login($param);

    /**
     * 博库的微信用户登录
     * @param string unionid varchar(32)	bookgoal用户的unionid
     * @param int type tinyint(1)	用户类型，1:小程序 2:布狗学习服务号 3:官网
     * @param string appid varchar(32)	应用ID
     * @param string openid varchar(32)	不同类型bookgoal用户的openid
     * @param string nickname varchar(255)	用户昵称
     * @param string headimgurl varchar(255)	用户头像
     * @param int sex 可选 微信资料
     * @param string city 可选 微信资料
     * @param string country 可选 微信资料
     * @param string province 可选 微信资料
     * @return
     * array (
     *  'user_id' => 69459,
     *  'from_appid' => 'wx0457a3c4233e83f8',
     *  'lib_mp_auth' => 0,
     * )
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function wxlogin($param) ;

    /**
     * 馆用户的微信登录
     *
     * @param int from_aid
     * @param string appid varchar(32)    应用ID
     * @param string openid varchar(32)    不同类型bookgoal用户的openid
     * @return
     * array (
     *  'uid' => 69459,
     *  'lib_uid' => '3',
     *  'auth_data' => [    //当无绑定的uid信息时，该信息为空
     *      'uid' => 69459,
     *      'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI11Y',
     *      'from_appid' => 'wx0457a3c4233e83f8',
     *      'from_aid' => '10',
     *      'library' => '中南民族大学图书馆',
     *      'headimgurl' => 'aaa',
     *      'lib_mp_auth' => 0,
     *   ],
     * )
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function libUserLogin($param);

    /**
     * 根据uid数组获取头像、昵称等微信数据
     * @param $uids
     * @return array 用户信息数组，用户信息含nickname,headimgurl mobile_number id_card
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function getUsers($uids, $aid=0, $app_code = '');

    /**
     * 在手机上绑定读者证
     * @param int uid 用户表id
     * @param int from_aid 来源待绑定图书馆aid
     * @param string reader_id 绑定读者证号
     * @param string name 可选 读者证姓名
     * @param string password 可选 读者证密码
     * @param int login_from_3rd 可选 用户是否是从第三方渠道界面输入登陆账号密码,即无法通过接口直接验证登陆账号的有效性。目前仅中南财大用的金智统一登录
     * @return array
     * @example
     * [
     *   'readers_id' => 54,
     *   'name' => '林丽微',
     *   'qcode' => '81755948550000000054'
     * ]
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bindReader($uid, $from_aid, $reader_id, $name, $password='', $login_from_3rd=0, $phone='');

    /**在手机上绑定读者证 原mlogin接口
     * @param int $uid
     * @param int $from_aid
     * @param string $reader_id
     * @param string $name
     * @param string $password
     * @return array readers_id,reader_aid,name,qcode,borrow_num,show_rdtype,show_unit
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bindReaderByRdinfo($uid, $from_aid, $reader_id, $name, $password);

    /**
     * 解除微信用户的读者证绑定
     * @param int $UID
     * @param int $readers_id
     * @param string $reader_id 解绑的读者证号，如传递将覆盖readers_id参数
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function unbindReader($uid, $readers_id, $reader_id='');

    /**
     * 博库用户绑定手机号
     * @param $uid
     * @param $mobile_number
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bindMobileNumber($uid, $mobile_number, $aid=0, $app_code='', $replace_binded=false);

    /**
     * 博库用户绑定身份证
     * @param $uid
     * @param $id_card
     * @param $realname
     * @param $aid
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bindIdCard($uid, $id_card, $realname, $aid=0, $app_code='');

    /**
     * 检查用户是否实名认证
     * @param $uid
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function checkIdCard($uid, $aid=0, $app_code='');

    /**
     * 博库用户绑定人脸
     * @param $uid
     * @param $img_base64
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bindFace($uid, $img_base64);

    /**
     * 博库用户解除人脸绑定
     * @param $uids
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function unbindFace($uids);
}

