<?php
/*
 * 全体微信图书馆项目公用控制器
 *
 * BaseController.php
 *
 * 本公用控制器主要用来定义请求与响应的公用助手方法
 */
namespace EasyUtils\Kernel\Controller;

use Firebase\JWT\JWT;
use think\facade\Env;
use EasyUtils\Kernel\Constant\ApiCodeConst;
use think\Validate;

abstract class BaseController extends \think\Controller
{
    /**
     * 基类控制器初始化方法
     * {@inheritDoc}
     * @see \think\Controller::initialize()
     */
    public function initialize()
    {
        //通过参数设置app_trace，控制页面显示跟踪日志
        $app_trace = input('param.trace');
        null !== $app_trace && Env::set('APP_TRACE', $app_trace);
	}

    /**
     * 验证入参是否必填
     * @param array|string $param_keys   必须存在的参数名
     * @param array $param   指定校验参数数组，如未指定，则校验post与get参数
     * @param array $strip_vals   需要公共过滤限制的值内容
     *                      多个过滤项传入数组或用逗号分隔的字符串,如'undefined,法轮'，['undefined','法轮']
     * @example
     * $this->checkMustParam('aid|number, reader_id, name|max:20');
     * $this->checkMustParam(['aid|number', 'reader_id|max:50']);
     */
    protected function checkMustParam($param_keys, $param=null, $strip_vals=[])
    {
        if (!$param) {
            $param = array_merge($this->request->post(), $this->request->get());
        }
        is_string($param_keys) && $param_keys = explode(',', $param_keys);

        $rule = [];
        $is_think51 = !defined('THINK_VERSION');
        //think5.1版本后，验证参数规则写法有变化
        foreach ($param_keys as $k => $v) {
            $v_arr    = explode('|', trim($v));
            $rule_k   = trim($v_arr[0]);
            if ($rule_k == 'reader_id' && $param['reader_id'] == 'undefined') {
                header("Content-type: application/json; charset=utf-8");
                exit(json_encode($this->retError("读者证号不能为空")->getData()));
            }
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
            if (headers_sent($filename, $linenum)) {
                $t = "在文件{$filename}第{$linenum}行有字符输出";
            } else {
                header("Content-type: application/json; charset=utf-8");
            }
            exit(json_encode($this->retError($validate->getError())->getData()));
        }
        if ($strip_vals) {
            is_string($strip_vals) && $strip_vals = explode(',', $strip_vals);
            foreach ($param as $k => $v) {
                if (in_array($v, $strip_vals)) {
                    if (headers_sent($filename, $linenum)) {
                        $t = "在文件{$filename}第{$linenum}行有字符输出";
                    } else {
                        header("Content-type: application/json; charset=utf-8");
                    }
                    $err_msg = "{$k}的值不能为{$v}";
                    exit(json_encode($this->retError($err_msg)->getData()));
                }
            }
        }
    }

    /**
     * 从token解析用户信息，适用于uid获取为可选项的接口
     * @return array|mixed|object
     */
    protected function parseMemberFromToken()
    {
        $request = $this->request;

        if (!empty($request->param('__uid')) && env('jwt_ignore')) {
            load_helper('token');

            $payload = [
                'uid'        => $request->param('__uid'),
                'from_appid' => empty($params['__appid']) ? '': $params['__appid'],
                'from_aid'   => empty($params['__aid']) ? 0: $params['__aid'],
                'login_time' => time(),
            ];
            $token = create_token($payload);
        } else {
            // 如果用户登陆后的所有请求没有jwt的token抛出异常
            $token = $request->param('access_token') ? $request->param('access_token') : $request->header('access_token');
        }

        if (strlen($token) < 8) {
            $request->member = null;
        } else {
            //解密token
            $member = JWT::decode($token, env('jwt_key'), array('HS256'));
            $request->member = $member;
        }
        return $request->member;
    }

	/**
	 * 统一返回api成功响应的输出内容(json格式)
	 * 
	 * @param array $data  成功返回的数据内容
	 * @param string $msg  成功提示语
	 * @return string|array      json格式字符串(code,msg,data)
	 */
	protected function retSuccess($data=[], $msg='', $code=0)
	{
//	    header("Content-type: application/json; charset=utf-8");
	    $response = array('code'=>$code, 'msg'=>$msg, 'data'=>$data);
        if (!is_laravel()) {
            return \think\Response::create($response, 'json');
        }
        return $response;
	}
	
	/**
	 * 统一返回api失败响应的输出内容(json格式)
	 * 
	 * @param string $msg  失败提示语
	 * @param number $code 失败状态码，默认为1
	 * @return string|array      json格式字符串(code,msg,data)
	 */
	protected function retError($msg="", $code=1, $data=[])
	{
	    $response = array('code'=>$code, 'msg'=>$msg, 'data'=>$data);
	    if (!is_laravel()) {
            return \think\Response::create($response, 'json');
        }
	    return $response;
	}
	
	/**
	 * 统一返回api失败响应的输出内容(json格式)
	 * 
	 * 调用示例:
	 * return $this->retByCode(ApiCodeConst::SYS_ERR)
	 *
	 * @param number $code 失败码，必须要在CodeConst类中定义
     * @return string|array      json格式字符串(code,msg,data)
	 */
	protected function retByCode($code)
	{
	    header("Content-type: application/json; charset=utf-8");
	    list($code, $code_tips) = ApiCodeConst::get($code);
	    
	    $response = array('code'=> $code, 'msg'=>$code_tips, 'data'=>[]);
        if (!is_laravel()) {
	        return \think\Response::create($response, 'json');
        }
        return $response;
	}

    /**
     * 访问频率限制
     * @param array $params_vals
     * @param int $time_interval
     */
    protected function checkReqFrequency($params_vals=[], $time_interval=60)
    {
        !is_array($params_vals) && $params_vals = [$params_vals];
        $backtrace = debug_backtrace();
        //访问频率限制为10秒
        $key = "{$backtrace[1]['class']}_{$backtrace[1]['function']}_" . implode('&', $params_vals);
        try {
            check_req_frequency($key, $time_interval);
        } catch (\Exception $e) {
            header("Content-type: application/json; charset=utf-8");
            exit(json_encode($this->retError($e->getMessage(), $e->getCode())->getData()));
        }
    }
}
