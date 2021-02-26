<?php
/*
 * 微信公众号认证相关通用方法
 * - 主要含用户oauth1 oauth2微信授权认证，获取微信用户信息
 *
 * WeAuth.php
 * 2019-04-17 16:41  guiyj<guiyj007@gmail.com>
 */
namespace EasyUtils\Wechat\Service;
use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\Kernel\Support\LibConf;

/**
 *  微信公众号认证相关通用方法
 */
class WeAuth
{
    /**
     * 图书馆aid
     * @var int
     */
    private $aid = 0;

    /**认证取到的openid
     * @var string
     */
    public $openid;

    /**
     * 取到微信用户的基本信息
     * @var array
     */
    public $wxuser;

    /**
     * 获取用户信息的接口范围
     * @var string
     */
    private $scope;
    private $originalData;

    /**
     * WeAuth constructor.
     * @param $type
     * @param $biz_key
     */
    public function __construct($aid, $scope = "snsapi_userinfo", $start_auth = false)
    {
        $this->aid   = $aid;
        $this->scope = $scope;
        $start_auth && $this->oauth();
    }

    /**
     * 开始微信授权，获取用户基本信息
     * @return string
     */
    public function getOpenid()
    {
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            $this->redirect2WxAuth($this->aid, 'snsapi_base');
            exit();
        } else {
            //获取code码，以获取openid
            $weObj = $this->initEasyWechat($this->aid);
            $user  = $weObj->oauth->scopes(['snsapi_base'])->user();
            if (!$user) {
                exit('获取用户授权失败，请重新确认');
            }

            $openid       = $user->getId();
//            $access_token = $user->getToken();
            return $openid;
        }
    }

    /**
     * 开始微信授权，获取openid
     * - 后续通过 $this->getUserinfo() 获取认证后取到的用户信息
     * - $scope=snsapi_base时，$this->getUserinfo()将为空
     * - 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。
     *
     * @param string $scope         oauth认证模式：snsapi_userinfo、snsapi_base
     * @return string
     */
    public function oauth()
    {
        $code       = isset($_GET['code']) ? $_GET['code'] : '';
        $url_aid    = isset($_GET['__aid']) ? $_GET['__aid'] : '';
        $scope      = $this->scope;
        $param_aid  = $this->getAid();
        $token_time = $this->session('token_time') ?: 0;

        /**
         * 1 从session取微信用户信息
         */
//        if (!$code && $this->session('wxuser') && $token_time > time() - 3600) {
        if ($this->session('wxuser') && $token_time > time() - 3600) {
            $this->wxuser = $this->session('wxuser');
            return $this->wxuser;
        }

        /**
         * 2 不存在code授权回调，带用户跳转获取code
         *   存在code但code不属于当前aid授权认证返回的，带用户跳转获取code
         */
        if (!$code || ($code && $param_aid != $url_aid)) {
            trace('redirect2WxAuth url');
            $this->redirect2WxAuth($param_aid, $scope);
            exit();
        }

        /**
         * 3 存在code，走认证流程：根据code走微信同步接口取openid，access_token，然后取关注者详情
         */
        // 获取 OAuth 授权结果用户信息
        $weObj = $this->initEasyWechat($param_aid);
        $err = '';
        try {
            $user  = $weObj->oauth->scopes([$scope])->user();
        } catch (\Exception $e) {
            $err = $e->getMessage();
            $user = '';
        }
        if (!$user) {
            trace("code={$code},param_aid={$param_aid},url_aid={$url_aid}", 'oauth_error');
            trace($this->session('wxuser'), 'oauth_error');
            $this->session('wx_redirect', '');
            biz_exception('获取微信授权失败，请重新打开页面确认!');
//            exit('获取微信授权失败，请重新打开页面确认!<br>' . $err);
        }

        $openid       = $user->getId();
        $access_token = $user->getToken();
        $this->openid = $openid;
        $this->wxuser = [];
        $this->originalData = $user->getOriginal();

        /**
         * 4. snsapi_userinfo模式取关注者详细信息存入本地。
         *      如果为空，说明异常发生，仅存入空用户信息，返回openid
         * (注：$this->originalData 中有scope字段时，是base模式，否则是snsapi_userinfo模式，可以取到nickname)
         */
        if (!empty($this->originalData['nickname'])) {
//        if ('snsapi_userinfo' == $scope) {
            $userinfo = $this->originalData;    //snsapi_userinfo
            if (!empty($userinfo['nickname'])) {
                $this->wxuser = array(
                    'openid'     => $openid,
                    'nickname'   => $userinfo['nickname'],
                    'sex'        => intval($userinfo['sex']),
                    'location'   => $userinfo['country'] . '-' . $userinfo['province'] . '-' . $userinfo['city'],
                    'headimgurl' => $userinfo['headimgurl'],
                    'unionid'    => !empty($userinfo['unionid']) ? $userinfo['unionid'] : '',
                );
            }
        } else {
            /**
             * 5. 即使是snsapi_base模式，先尝试根据openid获取用户信息；
             *    如未获取到（用户未关注公众号），再改用snsapi_userinfo模式让用户授权拉取用户信息
             */
            $real_scope = isset($this->originalData['scope']) ? $this->originalData['scope'] : 'snsapi_userinfo';
            //先尝试根据openid获取用户信息；
            $userinfo = $weObj->user->get($openid);
            if (! empty($userinfo['nickname'])) {
                $this->wxuser = array(
                    'openid'     => $openid,
                    'nickname'   => $userinfo['nickname'],
                    'sex'        => intval($userinfo['sex']),
                    'location'   => $userinfo['country'] . '-' . $userinfo['province'] . '-' . $userinfo['city'],
                    'headimgurl' => $userinfo['headimgurl'],
                    'unionid'    => !empty($userinfo['unionid']) ? $userinfo['unionid'] : '',
                );
            } else {
                if ('snsapi_userinfo' == $real_scope) {
                    //如果已经是snsapi_userinfo模式，但是还取不到用户信息，则异常退出
                    exit('获取用户信息失败，请重试');
                }
                //如未获取到（用户未关注公众号），再改用snsapi_userinfo模式让用户授权拉取用户信息
                $this->redirect2WxAuth($param_aid, 'snsapi_userinfo');
            }
        }

        $this->session('user_token', $access_token);
        $this->session('token_time', time());
        $this->session('wxuser', $this->wxuser);
        $this->session('openid', $openid);
        $this->session('wx_redirect', '');

        return $this;
    }

    /**
     * 根据oauth模式，跳转到微信授权认证链接
     * @param $aid
     * @param $scope
     */
    public function redirect2WxAuth($aid, $scope)
    {
        //当前页面设为callback url。并将url加上aid参数，以识别是哪个aid获取的code回跳链接
//        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $wx_param = LibConf::weConf($aid);
        if (isset($wx_param['is_auth']) && $wx_param['is_auth'] == 1){
            $url = rtrim(env('wx_auth_site_url_open'), '/') .  $_SERVER['REQUEST_URI'];
        }else{
            $url = rtrim(env('wx_auth_site_url'), '/') .  str_replace('/v2/', '/', $_SERVER['REQUEST_URI']);
        }

        $url = replace_url_param($url, ['__aid' => $aid]);
        if (! $url) {
            $this->session('wx_redirect', '');
            exit('获取用户授权失败');
        }

        $weObj = $this->initEasyWechat($aid);
        $weObj->oauth->scopes([$scope])->redirect($url)->send();
    }

    /**
     * @param $original_data
     * @example
     * array (
    'access_token' => '20_QXiEHNYNDVVNfLI_gD5PCiv33ixzZeo1UAWOk0GcLyI8tNzgH8QvtYTZp9P9COLbwfM70wumWHTJ944RnUDq2w',
    'expires_in' => 7200,
    'refresh_token' => '20_8lZohu_SedIOEkh5abcERMLazKhhP5xo1rcFh9ANZZxjnic8moPMbpWqllAsdUiGJbpqOCQMzsFgOhAJh7XluA',
    'openid' => 'o2WNQxIvAnXZ6CWBqVn4c1gHGT9A',
    'scope' => 'snsapi_base',
    ),
     */
    public function getOriginal($original_data)
    {
        $this->originalData = $original_data;
    }

    /**
     * 获取认证成功后的微信用户信息
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
    public function getUserinfo()
    {
        return $this->wxuser;
    }

    public function initEasyWechat($aid)
    {
        $wx_param = LibConf::weConf($aid);
        if (isset($wx_param['is_auth']) && $wx_param['is_auth'] == 1){
            return HandlerFactory::easyWechat(HandlerFactory::OPEN_PLATRORM_WE, $aid);
        }else{
            return HandlerFactory::easyWechat(HandlerFactory::OFFICIAL_ACCOUNTS, $aid);
        }
    }

    /**
     * 获取图书馆aid
     * @return int
     */
    public function getAid()
    {
        return $this->aid;
    }

    /**
     * 根据aid，存取同一用户的session信息
     * @param string $key   - 存取的key
     * @param mixed $val    - 存入的值。如仅取session，该参数不用传
     */
    private function session($key, $val = null)
    {
        $aid    = $this->getAid();
        $sess_key = "{$aid}_{$key}";
        if (null === $val) {
            return session($sess_key);
        } else {
            return session($sess_key, $val);
        }
    }

}
