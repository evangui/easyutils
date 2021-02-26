<?php
/*
 * 类操作句柄工厂
 *
 * HandlerFactory.php
 * 2019-04-17 16:41  guiyj<guiyj007@gmail.com>
 *
 * 用于各种资源操作句柄的生成。
 * 默认都为单例实现
 */
namespace EasyUtils\Kernel\Support;

use app\ali\logic\AliMiniApp;
use app\weixin\logic\Wxapp;
use EasyUtils\Kernel\constant\WeixinConst;
use EasyUtils\Kernel\traits\SingletonTrait;
use EasyUtils\ocr\Service\BaiduOcr;
use EasyUtils\ocr\Service\WxOcr;
use EasyUtils\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use WechatPay\GuzzleMiddleware\Util\PemUtil;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;

class HandlerFactory
{
    use SingletonTrait;

    const MINI_PROGRAM      = 1;    //小程序
    const OFFICIAL_ACCOUNTS = 2;    //服务号
    const PAYMENT = 3;              //微信支付
    const MICRO_MERCHANT = 4;       //微商户
    const OPEN_PLATRORM_WE = 5;     //微信开放平台代公众号实现业务
    const OPEN_PLATRORM_WXAPP = 6;  //微信开放平台代小程序实现业务
    const WEB_SITE_LOGIN = 7;       //网站登录

    const INSTANCE_KEY_PREFIX = 'HandlerFactory_';

    /**
     * 获取redis数据库对象实例
     * @param array $options
     * @return \Redis
     */
    public static function redis($options = [], $conf_key='')
    {
        //默认使用全局redis配置
        $conf = is_think5_1() ? config('redis.') : config('redis');

        //如果指定了conf_key则，使用指定的redis配置
        if (!empty($conf[$conf_key])) {
            $conf = $conf[$conf_key];
        }
        //如果指定了传入参数覆盖redis配置，则覆盖redis配置
        $options = array_merge($conf, $options);

        $instance_key = md5(self::INSTANCE_KEY_PREFIX . __FUNCTION__.json_encode($options) . $conf_key);
        $con = self::instance($instance_key);
        if ($con) {
            return $con;
        }

        $con = new \Redis();
        // 连接redis数据库
        $con->connect($options['host'], $options['port'], 5);
        // 认证密码
        $con->auth($options['pass']);
        // 切换到指定库
        $con->select($options['index']);
        // 设置key前缀
        $con->setOption(\Redis::OPT_PREFIX, $options['prefix']);

        self::instance($instance_key, $con);
        return $con;
    }

    /**
     * 获取easy wechat应用处理句柄
     * @param string $type          - 类型编号：1 mini_program, 2 Official Accounts
     * @param string $biz_key       - 业务放关键词。小程序为小程序名简称，公众号为图书馆aid
     * @param bool $use_singleton   - 是否使用单例句柄
     * @return \EasyUtils\OfficialAccount\Application | \EasyUtils\MiniProgram\Application | \EasyUtils\MicroMerchant\Application | \EasyUtils\OpenPlatform\Authorizer\MiniProgram\Application | \EasyUtils\Payment\Application | \EasyUtils\OpenPlatform\Authorizer\OfficialAccount\Application
     */
    public static function easyWechat($type, $biz_key, $use_singleton = true)
    {
        //之前的接口，如果是公众号接口，判断是否授权，授权的走第三方接口
        if ($type==self::OFFICIAL_ACCOUNTS){
            $wx_param = LibConf::weConf($biz_key);
            if (isset($wx_param['is_auth']) && $wx_param['is_auth'] == 1){
                $type = self::OPEN_PLATRORM_WE;
            }
        }
        //之前的接口，如果是小程序接口，判断是否授权，授权的走第三方接口
        if ($type==self::MINI_PROGRAM){
            $biz_key_arr = explode('-', $biz_key);
            $app_type = $biz_key_arr[0];
            if (!empty($biz_key_arr[1])) {
                $aid = $biz_key_arr[1];
                $wx_param = LibConf::wxConf($aid, $app_type);
                if (!empty($wx_param['appid']) && !empty($wx_param['is_auth'])){
                    $type = self::OPEN_PLATRORM_WXAPP;
                }
            }
        }

        //静态单例获取
        if ($use_singleton) {
            $instance_key = md5(self::INSTANCE_KEY_PREFIX . __FUNCTION__ . "{$type}_{$biz_key}");
            $con = self::instance($instance_key);
            if ($con) {
                return $con;
            }
        }

        //config可选项
        $config = [
            'response_type' => 'array', //指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'log' => [
                'driver' => 'single',
                'level'  => 'debug',
                'file'   => env('runtime_path') . 'log/' . date('Ym') . '/' . date('d') . '_wechat.log',
            ],
        ];
        //根据类型初始化获取EasyUtils 操作句柄
        switch ($type) {
            case self::WEB_SITE_LOGIN:    //网站微信扫码登录，用博库自己的微信配置
                $conf = config("bg.wx_web_login");
                $config = array_merge($config, [
                    'app_id' => $conf['app_id'],
                    'secret' => $conf['secret'],
                    'token'  => $conf['token'],
                    'oauth' => [
                        'scopes'   => ['snsapi_login'], //snsapi_login
//                        'callback' => $callback_pre . 'user/h5auth/qrCallback',
                    ],
                ]);
                $handler = Factory::officialAccount($config);
                break;

            case self::MINI_PROGRAM:    //小程序
                $app_type = $app_type ?: WeixinConst::WXAPP_NAME_WXLIB;
                $conf = config("wxapp.{$app_type}");
                $config = array_merge($config, [
                    'app_id' => $conf['appid'],
                    'secret' => $conf['secret'],
                ]);
                $handler = Factory::miniProgram($config);
                break;

            case self::OFFICIAL_ACCOUNTS:   //公众号
                $aid = $biz_key;
                $wx_param = LibConf::weConf($aid);

                //根据aid获取图书馆微信配置信息
                $config = array_merge($config, [
                    'app_id'  => $wx_param['appid'],
                    'secret'  => $wx_param['appsecret'],
                    'token'   => $wx_param['token'],
                    'aes_key' => $wx_param['encodingaeskey'],
                ]);
                $handler = Factory::officialAccount($config);
                break;
            case self::MICRO_MERCHANT:  //微商户
                $conf = config("pay.wx_micro_merchant");
                $config = [
                    // 必要配置
                    'mch_id'           => $conf['MERCHANTID'], // 服务商的商户号
                    'key'              => $conf['KEY'], // API 密钥
                    'apiv3_key'        => $conf['APIV3_KEY'], // APIv3 密钥
                    // API 证书路径(登录商户平台下载 API 证书)
                    'cert_path'        => $conf['SSLCERTPATH'], // XXX: 绝对路径！！！！
                    'key_path'         => $conf['SSLKEYPATH'], // XXX: 绝对路径！！！！
                    // 以下两项配置在获取证书接口时可为空，在调用入驻接口前请先调用获取证书接口获取以下两项配置,如果获取过证书可以直接在这里配置，也可参照本文档获取平台证书章节中示例
                    'response_type' => 'array',
//                    'appid'            => 'wx931386123456789e' // 服务商的公众账号 ID
                    ];

                $handler = Factory::microMerchant($config);
                break;
            case self::OPEN_PLATRORM_WE:   //微信开放平台代公众号实现业务
                //获取图书馆appid
                $aid = $biz_key;
                $wx_param = LibConf::weConf($aid);

                $config = array_merge($config, config("wxapp.open_platform"));
                $openPlatform = Factory::openPlatform($config);
//                echo ($openPlatform->getPreAuthorizationUrl('http://wxlib.bookgoal.com.cn/weixin/wxopen.push/index'));die;
//                trace($openPlatform->access_token->getToken(), 'component_access_token'); //['component_access_token'=>'xxx']

                //获取刷新令牌
                $data         = $openPlatform->getAuthorizer($wx_param['appid']);
                if (!isset($data['authorization_info'])){
                    biz_exception('请先完成授权操作！');
                }
                $refreshToken = $data['authorization_info']['authorizer_refresh_token'];

                $handler = $openPlatform->officialAccount($wx_param['appid'],$refreshToken);

                break;
            case self::OPEN_PLATRORM_WXAPP:   //微信开放平台代小程序实现业务
                $open_config = array_merge($config, config("wxapp.open_platform"));
                $openPlatform = Factory::openPlatform($open_config);

                //获取刷新令牌
                $wx_param = LibConf::wxConf($aid, $app_type);
                $data     = $openPlatform->getAuthorizer($wx_param['appid']);
                if (!isset($data['authorization_info'])){
                    biz_exception('请先完成授权操作！');
                }
                $refreshToken = $data['authorization_info']['authorizer_refresh_token'];
                $handler = $openPlatform->miniProgram($wx_param['appid'], $refreshToken);
                break;
            case self::PAYMENT: //微信支付
                $conf = config("pay.wechat.miniapp");
                $config = array_merge($config, [
                    'app_id'           => $conf['APPID'],
                    'mch_id'           => $conf['MERCHANTID'], // 服务商的商户号
                    'key'              => $conf['KEY'], // API 密钥
                    // API 证书路径(登录商户平台下载 API 证书)
                    'cert_path'        => $conf['SSLCERTPATH'], // XXX: 绝对路径！！！！
                    'key_path'         => $conf['SSLKEYPATH'], // XXX: 绝对路径！！！！
                    // 将上面得到的公钥存放路径填写在这里
                    'rsa_public_key_path' =>$conf['RSA_PUBLIC_KEY_PATH'], // XXX: 绝对路径！！！！
//                  'notify_url'         => '默认的订单回调地址',     // 你也可以在下单时单独设置来想覆盖它
                ]);
                $handler = Factory::payment($config);
                break;
            default:
                biz_exception('不支持的wechat实例化类型');
        }

        $use_singleton && self::instance($instance_key, $handler);
        return $handler;
    }

    /**
     * 获取ocr识别类对象实例
     * @param array $options
     * @return \EasyUtils\ocr\Service\IOcr
     */
    public static function ocr($engine='baidu')
    {
        $instance_key = md5(self::INSTANCE_KEY_PREFIX . __FUNCTION__.$engine);
        $handler = self::instance($instance_key);
        if ($handler) {
            return $handler;
        }
        if ($engine == 'baidu') {
            $handler = new BaiduOcr();
        } else {
            $handler = new WxOcr();
        }
        self::instance($instance_key, $handler);
        return $handler;
    }

    /**
     * 获取支付宝aop封装客户端类的处理句柄
     * @param string $app_name       - 支付宝应用名
     * @param bool $use_singleton   - 是否使用单例句柄
     * @return \AopClient
     */
    public static function alipayAop($app_name='wxlib', $use_singleton = true)
    {
        //静态单例获取
        if ($use_singleton) {
            $instance_key = md5(self::INSTANCE_KEY_PREFIX . __FUNCTION__ . "_{$app_name}");
            $con = self::instance($instance_key);
            if ($con) {
                return $con;
            }
        }

        $extend_path = env('extend_path');

        $conf = config("aliapp.{$app_name}");

        if (empty($conf['app_cert_path'])) {
            require_once $extend_path . 'alipay/aop/AopClient.php';
            require_once $extend_path . 'alipay/aop/AopCertification.php';
            $aop = new \AopClient();
            $aop->alipayrsaPublicKey = $conf['public_key'];
        } else {
            require_once $extend_path . 'alipay/aop/AopCertClient.php';
            require_once $extend_path . 'alipay/aop/AopCertification.php';
            $aop = new \AopCertClient ();
            $aop->isCheckAlipayPublicCert = true;//是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
            $aop->alipayrsaPublicKey = $aop->getPublicKey($conf['alipay_cert_path']);//调用getPublicKey从支付宝公钥证书中提取公钥
            $aop->appCertSN = $aop->getCertSN($conf['app_cert_path']);//调用getCertSN获取证书序列号
            $aop->alipayRootCertSN = $aop->getRootCertSN($conf['root_cert_path']);//调用getRootCertSN获取支付宝根证书序列号
        }

        //1、execute 使用
        $aop->rsaPrivateKey = $conf['private_key'];
        $aop->gatewayUrl = !empty($conf['gateway_url']) ? $conf['gateway_url'] : 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $conf['app_id'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';

        $use_singleton && self::instance($instance_key, $aop);
        return $aop;
    }

    /**
     * @param string $engine
     * @param string $app_name
     * @return \app\alipay\logic\AliMiniapp|Wxapp|\EasyUtils\Wechat\Service\Wxapp|null
     */
    public static function miniAppSvc($engine='wx', $app_name='wxlib', $aid=0)
    {
        $instance_key = md5(self::INSTANCE_KEY_PREFIX . __FUNCTION__.$engine);
        $handler = self::instance($instance_key);
        if ($handler) {
            return $handler;
        }
        if ($engine == 'ali') {
            $handler = \app\alipay\logic\AliMiniapp::getInstance($app_name);
        } else {
            $handler = Wxapp::getInstance($app_name, $aid);
        }
        self::instance($instance_key, $handler);
        return $handler;
    }

    /**
     * 获取WechatPayMiddleware应用处理句柄
     * @param array $conf 微信配置信息
     * @param bool $use_singleton   - 是否使用单例句柄
     * @return Client
     */
    public static function wechatPayMiddleware($conf, $use_singleton = true)
    {
        //静态单例获取
        if ($use_singleton) {
            $instance_key = md5(self::INSTANCE_KEY_PREFIX . __FUNCTION__ . "wechatpay");
            $con = self::instance($instance_key);
            if ($con) {
                return $con;
            }
        }

        $merchantPrivateKey = PemUtil::loadPrivateKey($conf['SSLKEYPATH']);
        $wechatpayCertificate = PemUtil::loadCertificate($conf['WECHATCERTPATH']);
        // 构造一个WechatPayMiddleware
        $wechatpayMiddleware = WechatPayMiddleware::builder()
            ->withMerchant($conf['MERCHANTID'], $conf['SERIAL_NUMBER'], $merchantPrivateKey) // 传入商户相关配置
            ->withWechatPay([ $wechatpayCertificate ]) // 可传入多个微信支付平台证书，参数类型为array
            ->build();

        // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
        $stack = HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');

        // 创建Guzzle HTTP Client时，将HandlerStack传入
        $handler = new Client(['handler' => $stack]);
        $use_singleton && self::instance($instance_key, $handler);
        return $handler;
    }

}
