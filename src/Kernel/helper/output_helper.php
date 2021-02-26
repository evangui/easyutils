<?php
//输出处理函数
use EasyUtils\Kernel\Support\Output;

/**
 * 抛出业务异常
 *
 * @param string    $code  异常代码 默认为1（业务处理错误）
 * @param integer   $msg   异常消息
 * @param string    $exception 异常类，默认用EasyUtils\Kernel\exception\BizException，可自定义指定
 *
 * @throws \EasyUtils\Kernel\exception\BizException
 */
function biz_exception($msg = '', $code = 1, $exception = '\EasyUtils\Kernel\exception\BizException', $data=[])
{
    if (null === $code) {
        $code = \EasyUtils\Kernel\Constant\ApiCodeConst::BIZ_ERR;
    }
    Output::throwBizException($msg, $code, $exception, $data);
}

/**
 * 将异常信息记录到日志文件的助手方法
 * @param Exception $e
 */
function trace_exception(\Exception $e, $complete_trace=false)
{
    log_trace("{$e->getCode()}:{$e->getMessage()}", 'alert');
    $strace_str = $e->getTraceAsString();
    if (!$complete_trace) {
        $trace_str = substr($strace_str, 0, strpos($strace_str, '#6') - 1);
    }
    log_trace("{$trace_str}", 'alert');
}

/**
 * 统一返回api成功响应的输出内容(json格式)
 *
 * @param array $data  成功返回的数据内容
 * @param string $msg  成功提示语
 * @return array      json格式字符串(code,msg,data)
 */
function ret_success($data=[], $msg='', $code=0)
{
//	    header("Content-type: application/json; charset=utf-8");
    $response = array('code'=>$code, 'msg'=>$msg, 'data'=>$data);
    return $response;
}

/**
 * 统一返回api失败响应的输出内容(json格式)
 *
 * @param string $msg  失败提示语
 * @param number $code 失败状态码，默认为1
 * @return array      json格式字符串(code,msg,data)
 */
function ret_error($msg="", $code=1, $data=[])
{
    $response = array('code'=>$code, 'msg'=>$msg, 'data'=>$data);
    return $response;
}

/**
 * 根据文件相对路径，生成文件存放的完整路径和可访问url
 * @param string $filePath public相对路径
 * @return string   完整url（需要配置APP_URL，切记，所有想被公开访问的文件都应该放在 storage/app/public 目录下。此外，你应该在public/storage [创建符号链接 ] (#the-public-disk) 来指向 storage/app/public 文件夹。）
 */
function file_complete_path($relative_path, $create_when_not_exist = false)
{
    if (!$relative_path) {
        $relative_path = date('Ymd') . '/' . generate_id() . ".jpeg";
    }
    $file_url  = env('site_url') . $relative_path;
    $file_path = str_replace('\\', DIRECTORY_SEPARATOR, env('root_path') . 'public/' . $relative_path);
    $dir = substr($file_path, 0, strrpos($file_path, '/'));
    if ($create_when_not_exist && !file_exists($dir)) {
        mkdir($dir, 0755, 1);
    }

    return [$file_path, $file_url];
}


