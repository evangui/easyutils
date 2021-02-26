<?php
/*
 * 博库业务处理通用功能助手函数
 *
 * common_biz_helper.php
 */

/**
 * 根据键名与图书馆aid, 获取通用业务配置
 * aid可以为空，则获取全局配置，否则为获取图书馆定制化配置
 *
 * @param  string $name 键名
 * @param  string $aid [可选]图书馆aid
 * @param  string $default_val 如未获取到值，设置默认值
 * @param  integer $cache_time 缓存时间，单位秒。如需重置缓存，请在调用该函数的url上加上参数 reset_cache=1
 * @return mixed
 * @throws \EasyUtils\Kernel\exception\BizException
 */
function conf_bg($name, $aid = 0, $default_val = null, $circ_place_id = 0, $cache_time = 3600) {
    $aid = intval($aid);
    return bg_module_conf('', $name, $aid, $default_val, $circ_place_id, $cache_time);
}

/**
 * 根据模块名、键名与图书馆aid, 获取图书馆业务配置
 * aid可以为空，则获取全局配置，否则为获取图书馆定制化配置
 * 如name为空，则获取内容为module下的所有配置项列表
 *
 * @param $name
 * @param $module
 * @param int $aid
 * @param null $default_val
 * @param int $cache_time
 * @return mixed|null
 * @throws \EasyUtils\Kernel\exception\BizException
 */
function bg_module_conf($module, $name, $aid = 0, $default_val = null, $circ_place_id=0, $cache_time = 3600) {
    try {
        $val = req_bg_conf($module, $name, $aid, $circ_place_id, ['_cacheTime'=> $cache_time]);
    } catch (\Exception $e) {
//        echo($e->getApiOutput('data'));die;
        throw $e;
    }
    return (null === $val['data']) ? $default_val : $val['data'];
}

/**
 * 通过api接口，请求config参数值
 * @param  string $name 键名
 * @param  string $aid [可选]图书馆aid
 * @param  integer $cache_time 缓存时间，单位秒. 如需重置缓存，请在调用该函数的url上加上参数 reset_cache=1
 * @return array
 * @throws \EasyUtils\Kernel\exception\BizException
 */
function req_bg_conf($module, $name, $aid = 0, $circ_place_id=0, $cacheOpt= ['_cacheTime'=> 3600])
{
    if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
        return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
    }
    $param = compact('name', 'module', 'aid', 'circ_place_id');
    $uri   = 'general/conf/getConf' . (!empty($_GET['reset_cache']) ? '?reset_cache=1' : '');
    $res = req_wxlib_v2($uri, $param);
    return $res;
}

/**
 * 根据公用配置文件名称前缀，加载配置信息到think config中去
 * 加载后，即可通过config()函数调用其中的配置
 * @param $conf_filename
 * @example use
 *  load_common_conf('api') 即加载EasyUtils/common/config/api_config.php
 */
function load_common_conf($conf_filename, $dir='')
{
    if (config($conf_filename )) {
        //return;
    }

    $conf_filename = str_replace('_config', '', $conf_filename);
    empty($dir) && $dir = __DIR__ . "/../config/";
    if (defined('THINK_VERSION')) {
        \think\Config::load("{$dir}{$conf_filename}_config.php");
    } elseif (class_exists(\think\facade\Config::class)) {
        \think\facade\Config::load("{$dir}{$conf_filename}_config.php");
    }

}

/**
 * 验证是否是thinkphp 5.1后的版本
 * @return boolean
 */
function is_think5_1() {
    return !defined('THINK_VERSION') && !is_laravel();
}

function is_laravel() {
    return class_exists(\Illuminate\Support\Facades\Log::class);
//    return defined('LARAVEL_START');
}

/**
 * tp5.0 兼容函数env
 */
if (! function_exists ( 'env' )) {
    function env($name, $default = null) {
        if (is_think5_1()) {
            return \think\facade\Env::get($name, $default);
        } else {
            $name = strtoupper($name);
            if (!defined($name)) {
                $env_file_val = \think\Env::get($name);
                return null === $env_file_val ? $default : $env_file_val;
            }
            return constant($name);
        }

    }
}

/**
 * 获取.env配置文件的参数值
 * @param $name
 * @param null $default
 * @return mixed
 */
function env_get($name, $default = null) {
    if (is_think5_1()) {
        return \think\facade\Env::get($name, $default);
    } else {
        return \think\Env::get($name, $default);
    }
}


/**
 * 验证当前操作的馆，是否是高校馆
 * @return boolean
 */
function is_school_lib($aid) {
    $test_lib_aids = [1, 2, 5];
    return $aid < 1000 && !in_array($aid, $test_lib_aids)  ? true : false;
}

/**
 * 博库邮件报警
 * @param $subject
 * @param $message
 * @return bool
 */
function bg_alarm_by_mail($subject, $message, $to_mails=null)
{
    $to_mails = $to_mails ?: config('bg.alarm_to_mails');
    if (!$to_mails) {
        biz_exception('未配置邮件收件人');
    }
    return bg_send_mail($to_mails, $subject, $message);
}

/**
 * 博库发送系统邮件通用方法
 * @param $to
 * @param $subject
 * @param $message
 * @param null $additional_headers
 * @param null $additional_parameter
 * @return bool
 */
function bg_send_mail($to, $subject, $message, $additional_headers = null, $additional_parameter = null)
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        //Server settings
        $mail->SMTPDebug = 0;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = 'smtp.exmail.qq.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'guiyajun@bookgoal.com.cn';                     // SMTP username
        $mail->Password   = 'Aa654321';                               // SMTP password
        $mail->SMTPSecure = 'ssl';                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = 465;                                    // TCP port to connect to
        $mail->CharSet    = 'utf-8';                                    // TCP port to connect to
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        //Recipients
        $mail->setFrom('guiyajun@bookgoal.com.cn', '博库程序发送');
        if (is_string($to)) {
            $to = [$to];
        }
        foreach ($to as $_to) {
            $mail->addAddress($_to);     // Add a recipient
        }

//        $mail->addAddress('ellen@example.com');               // Name is optional
//        $mail->addReplyTo('info@example.com', 'Information');
//        $mail->addCC('cc@example.com');
//        $mail->addBCC('bcc@example.com');

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
//        $mail->Subject = '=?utf-8?B?' . base64_encode($subject) . '?=';
        $mail->Subject = $subject;
        $mail->Body    = $message;
//        $mail->AltBody = $message;

        return $mail->send();
    } catch (Exception $e) {
        trace("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * 验证是否是正确的isbn
 * @return boolean
 */
function is_isbn($isbn)
{
    return \EasyUtils\Kernel\Support\IsbnUtil::isIsbn($isbn);
}

/**
 * 去除isbn空格和-
 * @return boolean
 */
function kick_isbn($isbn)
{
    return \EasyUtils\Kernel\Support\IsbnUtil::kickIsbn($isbn);
}

//echo (miniapp_scene('cat=2&name=政策法规'));
/**
 * 根据query字符串，生成博库自定义的小程序的scene字符串
 * scene的规则为：aid,reader_id,readers_id,活动或比赛id,扩展参数（key1:value1;key2:value2）
 * 如将 aid=3008&oid=1680&sex=1转换成3008,,,,oid:1680;sex:1
 *
 * @param $query_str
 * @return string
 */
function miniapp_scene($query_str)
{
    parse_str($query_str, $arr);
    $aid = $reader_id = $readers_id = $id = '';
    if (isset($arr['aid'])) {
        $aid = $arr['aid'];
        unset($arr['aid']);
    }
    if (isset($arr['reader_id'])) {
        $reader_id = $arr['reader_id'];
        unset($arr['reader_id']);
    }
    if (isset($arr['readers_id'])) {
        $readers_id = $arr['readers_id'];
        unset($arr['readers_id']);
    }
    if (isset($arr['id'])) {
        $id = $arr['id'];
        unset($arr['id']);
    }
    $scene = "{$aid},{$reader_id},{$readers_id},{$id}";
    //因为中文不支持，所以将urlencode中的%换成@，注意小程序接收解析scene参数时，做对应反转换
    $arr && $scene .= "," . str_replace(['=', '%'], [':', '@'], http_build_query($arr, null, ';'));
    $scene = substr($scene, 0, 32);
    return $scene;
}

/**
 * 根据aid获取对应图书馆名称
 * @param $aid
 * @return string
 */
function get_libname_by_aid($aid)
{
    $lib = \EasyUtils\Kernel\Support\LibConf::get($aid, 'we');
    return isset($lib['title']) ? $lib['title'] : "lib{$aid}";

}


/**
 * 检测图书系统是否正常
 *
 * @param [int] $aid
 * @return void
 */
function check_libsys_ok($aid)
{
    try {
        $res = req_libsys('uar/monitor/checkSysOk', ['aid' => $aid], 1, 3);
    } catch (\Exception $e) {
        return false;
    }
    return 0 == $res['code'] ? true  : false;
}