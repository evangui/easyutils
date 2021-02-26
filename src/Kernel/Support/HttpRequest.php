<?php
namespace EasyUtils\Kernel\Support;

use think\Exception;

class HttpRequest
{
    /**
     * 通用curl发送http请求
     * http和https都可以请求
     */
    public static function send($url, $data = null, $header = null, $timeout=6, $proxy='', $additional_curl_opt=[])
    {
        $ch = curl_init ();

        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // gzip
        curl_setopt($ch, CURLOPT_ENCODING, "gzip"); // 关键在这里
        ! empty($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if (strpos ( $url, 'https://' ) !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 这个是重点。
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
        }

        if($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        if (! empty ( $data )) {
            curl_setopt ( $ch, CURLOPT_POST, 1 );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        }
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );

        foreach ((array)$additional_curl_opt as $k => $v) {
            curl_setopt($ch, $k, $v);
        }

        $res = curl_exec ( $ch );
        curl_close ( $ch );
        return $res;
    }

    /**
     * 发送流文件
     * @param  String  $url  接收的路径
     * @param  String  $file 要发送的文件
     * @return boolean
     * @example
     * $image = $_FILES["photo"]["tmp_name"];
     *   $fp    = fopen($image, "r");
     *   $file  = fread($fp, $_FILES["photo"]["size"]); //二进制数据流
     *   $this->sendStreamFile('http://wxlib.bookgo.com.me/entrance/bind/receiveImg', $file, true);
     *  //$this->sendStreamFile('http://wxlib.bookgo.com.me/entrance/bind/receiveImg', $image, false);
     *
     */
    public static function sendStreamFile($server_url, $file, $is_binary = false){
        if(!$is_binary && !file_exists($file)){
            throw new \Exception('文件不存在');
        }
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'content-type:application/x-www-form-urlencoded',
                'content' => $is_binary ? $file : file_get_contents($file)
            )
        );

        $context  = stream_context_create($opts);
        $response = file_get_contents($server_url, false, $context);
        return $response;
    }


    /**
     * Get client ip.
     *
     * @return string
     */
    public static function getClientIp()
    {
        if (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            // for php-cli(phpunit etc.)
            $ip = defined('PHPUNIT_RUNNING') ? '127.0.0.1' : gethostbyname(gethostname());
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
    }

    ////获得访客浏览器类型
    public static function getBrowser($ua){
        if ($ua && empty($_SERVER['HTTP_USER_AGENT'])) {
            biz_exception('获取访客操作系统信息失败');
        }

        $ua = $ua ?: $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/MSIE/i',$ua)) {
            $br = 'msie';
        }elseif (preg_match('/Firefox/i',$ua)) {
            $br = 'firefox';
        }elseif (preg_match('/Chrome/i',$ua)) {
            $br = 'chrome';
        }elseif (preg_match('/Safari/i',$ua)) {
            $br = 'safari';
        }elseif (preg_match('/Opera/i',$ua)) {
            $br = 'opera';
        }else {
            $br = 'other';
        }
        return $br;

    }


    ////获取访客操作系统
    public static function getOs($ua=''){
        if ($ua && empty($_SERVER['HTTP_USER_AGENT'])) {
            biz_exception('获取访客操作系统信息失败');
        }

        $ua = $ua ?: $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/win/i',$ua)) {
            $os = 'Windows';
        }elseif (preg_match('/mac/i',$ua)) {
            $os = 'MAC';
        }elseif (preg_match('/linux/i',$ua)) {
            $os = 'Linux';
        }elseif (preg_match('/unix/i',$ua)) {
            $os = 'Unix';
        }elseif (preg_match('/bsd/i',$ua)) {
            $os = 'BSD';
        }else {
            $os = 'Other';
        }
        return $os;
    }
}
