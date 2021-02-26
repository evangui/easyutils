<?php
/**
 * 微信信用办证功能接口封装类
 * PayScore.php
 * 2020-10-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\Wechat\Service;


use EasyUtils\Kernel\Support\HandlerFactory;
use GuzzleHttp\Exception\RequestException;
use WechatPay\GuzzleMiddleware\Util\AesUtil;
use WechatPay\GuzzleMiddleware\Util\PemUtil;

class PayScore
{
    const PERMISSION_URL = 'https://api.mch.weixin.qq.com/v3/payscore/permissions';
    const PAY_URL = 'https://api.mch.weixin.qq.com/v3/payscore/serviceorder';
    const NOTIFY_URL = '/v2/user/wx_pay_score_notify/commonCallBack';

    protected $config;
    
    public function __construct($config = [])
    {
        if (!$config) {
            $this->config = config("pay.wechat.payscore");
        } else {
            $this->config = $config;
        }
    }

    /**
     * 商户预授权获取预授权token 给 前端调用
     * @param string $pay_sn 订单号，必填    预授权成功时的授权协议号，要求此参数只能由数字、大小写字母_-*组成，且在同一个商户号下唯一
     * @param string $notify_url 回调地址
     * @return mixed
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function applyPermissions($pay_sn, $notify_url)
    {
        $data = [
            'appid' => $this->config['APPID'],
            'service_id' => $this->config['SERVICEID'],
            'authorization_code' => $pay_sn,
            'notify_url' => $notify_url,
        ];
        trace('wxcredit applyPermissions： $data=: ' . var_export($data, true));
        $options = [
            'json' => $data,
            'headers' => [ 'Accept' => 'application/json' ]
        ];
        return $this->request(self::PERMISSION_URL, $options);
    }

    /**
     * 查询与用户授权记录（openid）
     * @param string $pay_sn 订单号，必填    预授权成功时的授权协议号，要求此参数只能由数字、大小写字母_-*组成，且在同一个商户号下唯一
     * @param string $notify_url 回调地址
     * @return mixed
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function checkUserAuth($openid)
    {
        $url = self::PERMISSION_URL. "/openid/{$openid}" . '?appid='.$this->config['APPID'] . '&service_id='.$this->config['SERVICEID'];
        $options = [
            'headers' => [ 'Accept' => 'application/json' ]
        ];
        return $this->request($url, $options, 'GET');
    }

    /**
     * 创建支付分订单
     * @param array $param
     *  order_sn    订单号，必填
     *  service_introduction 服务信息，默认值：信用办证
     *  $notify_url 回调地址
     *  $attach_tag 其他附加的自定义参数：商户数据包可存放本订单所需信息，需要先urlencode后传入。 当商户数据包总长度超出256字符时，报错处理。
     *  risk_fund_type 风险金名称，可选值(DEPOSIT：押金 ADVANCE：预付款 CASH_DEPOSIT：保证金)，默认值：DEPOSIT
     * risk_fund_amount 风险金额，默认10000（单位分）
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function createOrder(
        $pay_sn,
        $service_introduction,
        $notify_url,
        $attach_tag,
        $risk_fund_amount=10000,
        $openid = ''
    ) {
//        $mode = 0;//先免模式   DEPOSIT：押金
        $mode = 1;//【先享模式】（评估不通过不可使用服务）
        if ($mode === 1) {
            $risk_fund_type = 'ESTIMATE_ORDER_COST';
            $need_user_confirm = false;
        } else {
            $risk_fund_type = 'DEPOSIT';
            $need_user_confirm = true;
        }
        $data = [
            'out_order_no' => strval($pay_sn),
            'appid' => $this->config['APPID'],
            'service_id' => $this->config['SERVICEID'],
            'service_introduction' => $service_introduction,
            'openid' => $openid,
            'time_range' => [
                'start_time' => 'OnAccept',
            ],
            'risk_fund' => [
                'name' => $risk_fund_type,
                'amount' => $risk_fund_amount >= 100000 ? 10000 : $risk_fund_amount
            ],
            'notify_url' => $notify_url,
            'attach' => $attach_tag,
            'need_user_confirm' => $need_user_confirm,
        ];
//        $data['post_payments'] = [
//            [
//                'name' => $param['payment_name'] ?? '押金',
//                'amount' => $param['order_amount']
//            ]
//        ];
        trace('create wxcredit order $data=: ' . var_export($data, true));
        $options = [
            'json' => $data,
            'headers' => [ 'Accept' => 'application/json' ]
        ];
        return $this->request(self::PAY_URL, $options);
    }

    /**
     * 取消支付分订单
     * @param string $order_sn 订单号，必填
     * @param string $reason 取消原因，默认值：用户取消
     * @return mixed
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function cancelOrder($order_sn, $reason = '')
    {
        $url = self::PAY_URL.'/'.$order_sn.'/cancel';
        $options = [
            'json' => [
                'appid' => $this->config['APPID'],
                'service_id' => $this->config['SERVICEID'],
                'reason' => $reason ? : '用户取消',
            ],
            'headers' => [ 'Accept' => 'application/json' ]
        ];
        return $this->request($url, $options);
    }

    /**
     * 查询支付分订单
     * @param string $order_sn 订单号，必填
     * @return mixed
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function queryOrder($order_sn)
    {
        $url = self::PAY_URL.'?out_order_no='.$order_sn.'&service_id='.$this->config['SERVICEID'].'&appid='.$this->config['APPID'];
        $options = [
            'headers' => [ 'Accept' => 'application/json' ]
        ];
        return $this->request($url, $options, 'GET');
    }

    /**
     * 完结支付分订单
     * @param array $param
     *  order_sn    订单号，必填
     *  payment_name 付费项目名称，默认值：押金
     *  order_amount 订单金额（单位分）
     * @return mixed
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function completeOrder($order_sn, $order_amount, $payment_name='押金', $extra_param=[])
    {
        $url = self::PAY_URL.'/'.$order_sn.'/complete';
        $options = [
            'json' => [
                'appid' => $this->config['APPID'],
                'service_id' => $this->config['SERVICEID'],
                'post_payments' => [
                    [
                        'name' => $payment_name,
                        'amount' => $order_amount
                    ]
                ],
                'time_range' => [
                    'end_time' => date('YmdHis'),
                ],
                'total_amount' => $order_amount
            ],
            'headers' => [ 'Accept' => 'application/json' ]
        ];
        return $this->request($url, $options);
    }

    /**
     * 回调接口验证签名
     * @param string $serialNumber  certificate serial number
     * @param string $message   message to verify
     * @param string $signautre signature of message
     *
     * @return bool
     */
    public function verify($serialNumber, $message, $signature)
    {
        $certificate = PemUtil::loadCertificate($this->config['WECHATCERTPATH']);
        $serialNo = PemUtil::parseCertificateSerialNo($certificate);
        $serialNumber = \strtoupper(\ltrim($serialNumber, '0')); // trim leading 0 and uppercase
        if ($serialNumber != $serialNo) {
            return false;
        }
        if (!in_array('sha256WithRSAEncryption', \openssl_get_md_methods(true))) {
            throw new \RuntimeException("当前PHP环境不支持SHA256withRSA");
        }
        $signature = \base64_decode($signature);
        $publicKey = \openssl_get_publickey($certificate);
        return \openssl_verify($message, $signature, $publicKey, 'sha256WithRSAEncryption');
    }

    /**
     * 生成小程序扩展参数
     * @param $data
     *  package 跳转微信侧小程序订单数据
     * @return mixed
     */
    public function getExtData($data)
    {
        $data['mch_id'] = $this->config['MERCHANTID'];
        $data['timestamp'] = time();
        $data['nonce_str'] = $this->getNonce();
        $data['sign_type'] = 'HMAC-SHA256';
        $data['sign'] = $this->makeSign($data, $this->config['KEY']);
        return $data;
    }

    /**
     * 敏感信息解密
     * @param $resource
     *  associated_data 附近数据
     *  nonce 随机串
     *  ciphertext 数据密文
     * @return bool|string
     */
    public function decryptToString($resource)
    {
        $aes = New AesUtil($this->config['APIV3_KEY']);
        return $aes->decryptToString($resource['associated_data'], $resource['nonce'], $resource['ciphertext']);
    }

    /**
     * 本地授权关系维护
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function localUserAuth($openid, $authed=null)
    {
        $redis = HandlerFactory::redis();
        $key = 'wxcreditAuth';
        if ($authed !== null) {
            if ($authed == 1) {
                $res = $redis->sAdd($key, $openid);
            } else {
                $res = $redis->sRem($key, $openid);
            }
            return $res;
        }
        return $redis->sIsMember($key, $openid);
    }

    /**
     * 调用微信支付中间件请求接口
     * @param $url
     * @param $options
     * @param string $method
     * @return mixed
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function request($url, $options, $method = 'POST')
    {
        $log_file = env('runtime_path') . 'log/' . date('Ym') . '/' . date('d') . '_wechatpay.log';
        try {
            file_put_contents($log_file, $url . var_export($options, true) ."\r\n", FILE_APPEND);
            $resp = HandlerFactory::wechatPayMiddleware($this->config)->request($method, $url, $options);
            file_put_contents($log_file, 'body: ' . $resp->getBody()."\r\n", FILE_APPEND);
            return json_decode($resp->getBody(), true);
        } catch (RequestException $e) {
            // 进行错误处理
            file_put_contents($log_file, $e->getMessage()."\r\n", FILE_APPEND);
            if ($e->hasResponse()) {
                $res = json_decode($e->getResponse()->getBody(), true);
                if (isset($res['message'])) {
                    biz_exception($res['message']);
                }
            }
            biz_exception($e->getMessage());
        }
    }

    /**
     * 生成签名
     * @param array $data
     * @return string
     */
    public function makeSign($data, $key)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->ToUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$key;
        //签名步骤三：HMAC-SHA256
        $string = hash_hmac("sha256",$string ,$key);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    protected function toUrlParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * Get nonce
     *
     * @return string
     */
    protected function getNonce()
    {
        static $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 32; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}