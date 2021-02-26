<?php
/*
 * 网络相关公用助手函数，含通用http请求方法
 *
 * net.php
 */
use EasyUtils\Kernel\Constant\SysNameConst;
use EasyUtils\Kernel\Constant\ApiCodeConst;

/**
 * 请求自助借还系统接口
 *
 * @param string $uri   请求资源路径，如api/book/doLoan
 * @param array $param  接口请求参数
 * @return array
 */
function req_libsys($uri, $param, $errorRetryTimes=2, $timeout=6)
{
    return req_bg_api(SysNameConst::SYS_LIBSYS, $uri, $param, $errorRetryTimes, $timeout);
}

/**
 * 请求微信图书馆对外接口
 *
 * @param string $uri   请求资源路径，如 uar/library/searchBook
 * @param array $param  接口请求参数
 * @return array
 */
function req_wxlib($uri, $param, $errorRetryTimes=2, $timeout=6)
{
    return req_bg_api(SysNameConst::SYS_WXLIB, $uri, $param, $errorRetryTimes, $timeout);
}

/**
 * 请求微信图书馆对外2.0接口
 * - 当接口返回code不为0时，则统一会抛出BizException异常，请进行拦截该异常
 *    对应原接口code与msg, 可通过拦截异常对象的getCode与getMessage方法获取
 * - 当异常拦截后，可通过异常对象的 getData方法，获取详细异常调用信息
 *   如：dump($exception->getData('data'))
 *
 * @param string $uri   请求资源路径，如 uar/library/searchBook
 * @param array $param  接口请求参数
 * @return array
 */
function req_wxlib_v2($uri, $param, $errorRetryTimes=2, $timeout=6)
{
    return req_bg_api(SysNameConst::SYS_WXLIB_V2, $uri, $param, $errorRetryTimes, $timeout);
}

/**
 * 请求数据展示平台 对外接口
 *
 * @param string $uri   请求资源路径，如 uar/library/searchBook
 * @param array $param  接口请求参数
 * @return arrayhttp_curl
 */
function req_bg_data($uri, $param, $errorRetryTimes=2, $timeout=6)
{
    return req_bg_api(SysNameConst::SYS_DATA, $uri, $param, $errorRetryTimes, $timeout);
}

/**
 * 请求微信图书馆对外接口
 * - 当接口返回code不为0时，则统一会抛出BizException异常，请进行拦截该异常
 *    对应原接口code与msg, 可通过拦截异常对象的getCode与getMessage方法获取
 * - 当异常拦截后，可通过异常对象的 getData方法，获取详细异常调用信息
 *   如：dump($exception->getData('data'))
 *
 * @param string $uri   请求资源路径，如 uar/library/searchBook
 * @param array $param  接口请求参数
 * @return array
 */
function req_bg_api($sys_code, $uri, $param, $errorRetryTimes=2, $timeout=6)
{
    // 组装接口请求地址
    $allowed_api_sys = array_keys(SysNameConst::$constsMap);
    if (!in_array($sys_code, $allowed_api_sys)) {
        biz_exception(null, "{$sys_code}不是合法的接口系统标志");
    }

    load_common_conf('api');
    $url = config("apiurl.{$sys_code}") . $uri;
    $time_start = microtime(true);


    // 请求接口
    $current_error_times = 0;
    $res_arr = [];
    while (!isset($res_arr['code']) && $current_error_times < $errorRetryTimes) {
        $res     = \EasyUtils\Kernel\Support\HttpRequest::send($url, $param, null, $timeout);
        $res_arr = json_decode($res,true);
        $current_error_times++;
    }

    $used_time  = number_format(microtime(true) - $time_start, 4);
    if (class_exists(\Illuminate\Support\Facades\Log::class)) {
        Illuminate\Support\Facades\Log::info("【req_bg_api】输入数据>>>>>>>{$url}：" .var_export($param,true));
        Illuminate\Support\Facades\Log::info("【req_bg_api】{$used_time}s 输出数据<<<<<<<{$url}：\r\n{$res}\r\n" . var_export($res_arr, true));
    } else {
        trace("【req_bg_api】输入数据>>>>>>>{$url}：" . var_export($param, true));
        trace("【req_bg_api】{$used_time}s 输出数据<<<<<<<{$url}：\r\n{$res}\r\n" . var_export($res_arr, true));
    }

    $err_data = [
        'url' => $url,
        'param' => $param,
        'used_time' => $used_time,
        'output' => $res,
    ];
    // 异常状态处理
    if (!isset($res_arr['code'])) {
        //设置错误其他数据，通过$e->getData()获取
        throw new \EasyUtils\Kernel\exception\BizException('网络请求失败', ApiCodeConst::NETWORK_ERR, $err_data);
    }
    // 错误码当做异常处理
    if ($res_arr['code'] != ApiCodeConst::BIZ_SUCCESS) {
        $err_data['err_code'] = $res_arr['code'];
        $err_data['err_msg'] = $res_arr['msg'];
        throw new \EasyUtils\Kernel\exception\BizException($res_arr['msg'], $res_arr['code'], $err_data);
    }
    return $res_arr;
}


function req_bg_rpc($svc_uri, $param, $errorRetryTimes=2, $timeout=6)
{
    return \EasyUtils\Apibase\RpcHandler::request($svc_uri, $param, $errorRetryTimes, $timeout);
}

/**
 * 通用curl发送http请求
 * http和https都可以请求
 */
if (! function_exists ( 'http_curl' )) {
    function http_curl($url, $data = null, $header = null, $timeout=6, $proxy='', $additional_curl_opt=[]) {
        return \EasyUtils\Kernel\Support\HttpRequest::send($url, $data, $header, $timeout, $proxy, $additional_curl_opt);
    }
}

//访问频率限制
function check_req_frequency($frequency_limit_key, $limit_seconds=1)
{
    $last_pass_request_time = cache($frequency_limit_key);
    $now_time               = microtime(true);
//    v($now_time - $last_pass_request_time, 0);
    if ($now_time - $last_pass_request_time < $limit_seconds) {
        $left_sec = intval($limit_seconds - ($now_time - $last_pass_request_time));
        biz_exception("请求太频繁，请间隔{$left_sec}秒后再试", ApiCodeConst::MAX_EXCEED_ERR);
    }
    return cache($frequency_limit_key, $now_time);
}

function filegetcontent($url, $param=null, $max_count=3, $timeout=600, $method='post') {
    $count = 0;
    $context['http'] = [
        'method' => $method,
        'timeout' => $timeout,
        'Content-type' => 'text/html;charset=utf-8',
    ];
    if ( is_array($param) ) $context['http']['content'] = http_build_query($param, '', '&');

    while ( $count<$max_count && ($content=file_get_contents($url, false, stream_context_create($context)))===FALSE ) {
        $count++;
    }

    return $content;
}
