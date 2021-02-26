<?php
/*
 * 应用程序默认入口文件
 *
 * Index.php
 * 2019年1月10日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 * 用于默认入口文件，如提供接口，请另使用带有准确含义的其他控制器文件。
 * 注: 这里是文件用途详细说明。可简要说明特殊接口的注意事项
 */

namespace EasyUtills\AI\AIUI;

/**
 * 科大讯飞AIUI服务
 * Class AIUIService
 * @package service
 */
class AIUIService
{
    const API_URL = 'http://openapi.xfyun.cn/v2/aiui';
    const APP_ID = '600e8d7f';                    //讯飞AIUI开放平台注册申请应用的应用ID(appid)
    const API_KEY = '622ecb8d8a791a2dd023de9425c279f4';                    //接口密钥，由讯飞AIUI开放平台提供，调用方管理
    const AUTH_ID = 'be7f5b9259c2f1e72a8192efb7e4b06a'; //用户唯一ID（32位字符串，包括英文小写字母与数字，开发者需保证该值与终端用户一一对应）
    const TOKEN = '*****';                     //后处理token
    const AES_KEY = '*****';                   //加密AES KEY

    /**
     * 构造函数
     * @param string $key 密钥
     * @param string $method 加密方式
     * @param string $iv iv向量
     * @param mixed $options 还不是很清楚
     */
    public function __construct()
    {
        $this->token = self::TOKEN;

        // key是必须要设置的
        $this->secret_key = self::AES_KEY;
        $this->method = "AES-128-CBC";
        $this->iv = self::AES_KEY;
        $this->options = OPENSSL_RAW_DATA;
    }

    /**
     * 将语音转换成文本
     *
     * @param string $data_type //text（文本），audio（音频）
     */
    function voice2text($file_path, $data_type = 'mp3')
    {
        // 个性化参数，需转义
        $pers_param = "{\\\"auth_id\\\":\\\"2894c985bf8b1111c6728db79d3479ae\\\"}";
        if ('mp3' == $data_type) {
            $data_type = 'audio';
            $file_path = $this->mp3ToWav($file_path);
            if (!$file_path) {
                biz_exception('mp3ToWav error');
            }
        } else {
            $data_type = 'audio';
        }

        $param = array(
            "scene" => "main",
            "result_level" => "plain",       //结果级别，可选值：plain（精简），complete（完整），默认 plain
            "aue" => "raw",
            "auth_id" => self::AUTH_ID,
            "data_type" => $data_type,
            "sample_rate" => "16000",
            //如需使用个性化参数：
            //"pers_param"=>$PERS_PARAM,
        );

        $curTime = time();
        $paramBase64 = base64_encode(json_encode($param));
        $checkSum   = md5(self::API_KEY . $curTime . $paramBase64);

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'X-CurTime:' . $curTime;
        $headers[] = 'X-Param:' . $paramBase64;
        $headers[] = 'X-CheckSum:' . $checkSum;
        $headers[] = 'X-Appid:' . self::APP_ID;
//        ve($headers);

        $fp = fopen($file_path, "rb");
        $bin_str = fread($fp, filesize($file_path));

        $res = $this->httpsRequest(self::API_URL, $bin_str, $headers);
        log_trace($res);
        $res = json_decode($res, true);
        if ('0' != $res['code']){
            biz_exception($res['desc'], $res['code']);
        }
        return $res['data'][0]['text'];
    }

    public function mp3ToWav($mp3_file, $save_wave_path='')
    {
        if (DIRECTORY_SEPARATOR == '\\') {  //windows平台
            $config = [
                'ffmpeg.binaries'  => 'D:\Program Files\ffmpeg-4.3.1\bin\ffmpeg.exe',
                'ffprobe.binaries' =>  'D:\Program Files\ffmpeg-4.3.1\bin\ffprobe.exe'
            ];
        } else {
            $config = [
                'ffmpeg.binaries'  => '/usr/local/ffmpeg/ffmpeg',
                'ffprobe.binaries'  => '/usr/local/ffmpeg/ffprobe',
            ];
        }

        $ffmpeg = \FFMpeg\FFMpeg::create($config);
        $video = $ffmpeg->open($mp3_file);
        !$save_wave_path && $save_wave_path = $mp3_file . '.wav';

//        $save_wave_path = '/home/www/bg_wxlib/public/html/tour_guide/test/test.wav';

        try {
            $res = $video->save(new \FFMpeg\Format\Audio\Wav(), $save_wave_path);
        } catch (\Exception $e) {
            throw $e;
            return '';
        }
        return $save_wave_path;
    }

    function httpsRequest($url, $post_data, $headers)
    {
        $headers = implode("\r\n", $headers) . "\n";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => $headers,
                'content' => $post_data,
                'timeout' => 10
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * 签名验证
     * @param $token token
     * @param $timestamp 时间戳
     * @param $rand 随机数
     * @param $aesKey $aesKey
     * @param $sign 客户端请求接口sign参数值
     * @return INT
     */
    public function checkAuth($sign, $timestamp, $rand, $key = '')
    {
        //按规则拼接为字符串
        $str = self::createSignature($this->token, $timestamp, $rand, $key);
        ///校验签名字符串：0为一致、-1为不一致
        if ($str !== $sign) {
            return -1;
        }
        return 0;
    }

    /**
     * 生成签名
     * @param $token
     * @param $timestamp
     * @param $rand
     * @param string $aesKey
     * @return string
     */
    private static function createSignature($token, $timestamp, $rand, $key = '')
    {
        //组装要排序的数组
        $arr = [$timestamp, $token, $rand];
        //字典序排序
        sort($arr);
        //拼接为一个字符串
        $str = implode('', $arr);
        //sha1加密
        return sha1($str);
    }

    /**
     *  加密
     * @param $plaintext string 要加密的字符串
     * @return string
     */
    public function encrypt($plaintext)
    {
        //加密采用AES的CBC加密方式，秘钥为16字节（128bit），初始化向量复用秘钥，填充方式为PKCS7Padding。
        //返回的消息要以同样的方式进行加密。
        //加密过程：padding->CBC加密->base64编码
        //$option 以下标记的按位或： OPENSSL_RAW_DATA 原生数据，对应数字1，不进行 base64 编码。OPENSSL_ZERO_PADDING 数据进行 base64 编码再返回，对应数字0。
        return openssl_encrypt($plaintext, $this->method, $this->secret_key, $this->options, $this->iv);
    }

    /**
     *  解密
     * @param $ciphertext string 要解密的字符串
     * @return string
     */
    public function decrypt($ciphertext)
    {
        //解密过程：base64解码->CBC解密->unpadding
        return openssl_decrypt($ciphertext, $this->method, $this->secret_key, $this->options, $this->iv);
    }

}