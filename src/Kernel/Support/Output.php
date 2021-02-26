<?php
namespace EasyUtils\Kernel\Support;

use EasyUtils\Kernel\Constant\ApiCodeConst;
use Illuminate\Support\Facades\Log;

class Output
{
    /**
     * 抛出业务异常
     *
     * @param string    $code  异常代码 默认为1（业务处理错误）
     * @param integer   $msg   异常消息
     * @param string    $exception 异常类，默认用EasyUtils\Kernel\exception\BizException，可自定义指定
     *
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public static function throwBizException($msg = '', $code = 1, $exception = '\EasyUtils\Kernel\exception\BizException', $data=[])
    {
        $e = $exception ?: '\EasyUtils\Kernel\exception\BizException';
        
        try {
            !$msg && $msg = ApiCodeConst::getTips($code);
        } catch (\InvalidArgumentException $apicode_exception) {
            $msg = '';
        }

        if (class_exists(\Illuminate\Support\Facades\Log::class)) {
            Log::alert("{$code}:{$msg}");
        } else {
            log_trace("{$code}:{$msg}", 'alert');
        }
        if (false === strpos($e, 'EasyUtils\Kernel\exception\BizException')) {
            throw new $e($msg, $code);
        } else {
            throw new $e($msg, $code, $data);
        }
    }

    /**
     * 统一返回api成功响应的输出内容(json格式)
     *
     * @param array $data  成功返回的数据内容
     * @param string $msg  成功提示语
     * @return array      json格式字符串(code,msg,data)
     */
    public static function retSuccess($data=[], $msg='', $code=0)
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
    public static function retError($msg="", $code=1, $data=[])
    {
        $response = array('code'=>$code, 'msg'=>$msg, 'data'=>$data);
        return $response;
    }

    /**
     * 统一返回api失败响应的输出内容(json格式)
     *
     * 调用示例:
     * return $this->retByCode(ApiCodeConst::SYS_ERR)
     *
     * @param number $code 失败码，必须要在CodeConst类中定义
     * @return array      json格式字符串(code,msg,data)
     */
    public static function retByCode($code)
    {
        list($code, $code_tips) = ApiCodeConst::get($code);

        $response = array('code'=> $code, 'msg'=>$code_tips, 'data'=>[]);
        return $response;
    }

    /**
     * 静态方法调用
     * @access public
     * @param  string $method 调用方法
     * @param  mixed  $args   参数
     * @return void
     */
    public static function __callStatic($method, $args)
    {
        //转发到str_helper.php
    }

}
