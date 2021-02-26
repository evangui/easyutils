<?php
/*
 * yar rpc服务端控制器
 *
 * YarController.php
 *
 * 本公用控制器主要用来定义请求与响应的公用助手方法
 */
namespace EasyUtils\Kernel\controller;

use EasyUtils\Kernel\constant\ApiCodeConst;
use Hprose\Http\Server;
use think\Validate;

/**
 * Yar控制器类
 */
abstract class AbstractRpcServer
{
    protected $server = null;
    protected $crossDomain =    true;
    protected $P3P         =    true;
    protected $debug       =    true;
    /**
     * 构造函数
     * @access public
     */
    public function __construct()
    {
        trace('[rpc param] '. request()->module() . '.' . input('get.c'));
        trace('[rpc param] ' . file_get_contents('php://input'));

        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }

        $this->server = new Server();
        $this->server->addInstanceMethods($this);

        $this->server->debug = $this->debug;
        $this->server->P3P = $this->P3P;
        $this->server->crossDomain = $this->crossDomain;
    }

    //启动
    public function start()
    {
        try {
            $this->server->start();
        } catch (\Exception $e) {
            ve($e->getMessage());
        }

    }

    /**
     * 验证入参是否必填
     * @param array|string $param_keys 必须存在的参数名
     * @throws \EasyUtils\Kernel\exception\BizException
     * @example
     * $this->checkMustParam('aid|number, reader_id, name|max:20');
     * $this->checkMustParam(['aid|number', 'reader_id|max:50']);
     */
    protected function checkMustParam($param_keys, $param)
    {
        is_string($param_keys) && $param_keys = explode(',', $param_keys);

        $rule = [];
        $is_think51 = !defined('THINK_VERSION');
        //think5.1版本后，验证参数规则写法有变化
        foreach ($param_keys as $k => $v) {
            $v_arr    = explode('|', trim($v));
            $rule_k   = trim($v_arr[0]);
            $rule_val = 'require' . (!empty($v_arr[1]) ? "|{$v_arr[1]}" : '');
            if ($is_think51) {
                $rule[$rule_k] = $rule_val;
            } else {
                $rule[] = [$rule_k, $rule_val];
            }
        }
        // 参数验证
        $validate = new Validate($rule);
        if ($validate->check($param) == false) {
            biz_exception($validate->getError());
        }
    }

    /**
     * 统一返回api成功响应的输出内容(json格式)
     *
     * @param array $data  成功返回的数据内容
     * @param string $msg  成功提示语
     * @return array      json格式字符串(code,msg,data)
     */
    protected function retSuccess($data=[], $msg='', $code=0)
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
    protected function retError($msg="", $code=1, $data=[])
    {
        $response = array('code'=>$code, 'msg'=>$msg, 'data'=>$data);
        return $response;
    }

    /**
     * 抛出自定义的异常错误格式，在客户端中捕捉此错误，转换成异常输出
     *
     * @param string $msg  失败提示语
     * @param number $code 失败状态码，默认为1
     * @return array      json格式字符串(code,msg,data)
     */
    protected function throwError($msg="", $code=1, $data=[])
    {
        //为了客户端方便将此方式输出，当做异常捕获，特设置e_code表示异常状态码
        $response = array('e_code'=>$code, 'msg'=>$msg, 'data'=>$data);
        return $response;
    }

    /**
     * 统一返回api失败响应的输出内容(json格式)
     *
     * 调用示例:
     * return $this->retByCode(ApiCodeConst::SYS_ERR)
     *
     * @param number $code 失败码，必须要在CodeConst类中定义
     * @return string      json格式字符串(code,msg,data)
     */
    protected function retByCode($code)
    {
        list($code, $code_tips) = ApiCodeConst::get($code);

        $response = array('code'=> $code, 'msg'=>$code_tips, 'data'=>[]);
        return $response;
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method, $args)
    {}
}
