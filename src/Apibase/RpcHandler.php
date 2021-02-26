<?php
/*
 * rpc服务调用的句柄类
 *
 * RpcHandler.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * rpc方法调用：依赖初始化rpc服务名，服务模块；最终执行时依赖魔术方法执行调用
 */
namespace EasyUtils\apibase;


use EasyUtils\Kernel\constant\SysNameConst;
use EasyUtils\Kernel\exception\BizException;
use EasyUtils\Kernel\Support\HandlerFactory;

class RpcHandler
{
    /**
     * 服务名
     * @var string
     */
    public $svc;

    /**
     * 控制模块名
     * @var string
     */
    public $ctl;

    /**
     * 执行方法名
     * @var string
     */
    public $action;

    /**
     * rpc调用配置
     * @var array
     */
    public $config;

    /**
     * 初始化最终rpc调用的核心参数（服务名、模块名，rpc调用配置）
     *
     * @param $svc
     * @param $ctl
     * @param array $rpc_config
     * @return $this
     */
    public function init($svc, $ctl, $rpc_config=[])
    {
        $this->svc = $svc;
        $this->ctl = $ctl;
        $this->config = $rpc_config;
        return $this;
    }


    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        $svc_uri = $this->svc . '.' . $this->ctl . '.' . $method;
        $param = [$svc_uri, $args];
        return call_user_func_array([$this, 'request'], $param);
    }

    /**
     * 调用请求封装
     * 解析调用参数、记录调用日志与异常信息识别
     *
     * @param string $svc_uri  服务路径,如用户服务的读者模块，则为user.reader
     * @param array $args
     * @param int $errorRetryTimes
     * @param int $timeout
     * @return array|mixed
     * @throws BizException
     */
    public static function request($svc_uri, $args, $errorRetryTimes=1, $timeout=6)
    {
        try {
            list($svc, $ctl, $action) = explode('.', $svc_uri);
        } catch (\Exception $e) {
            biz_exception('$uri参数不合法');
        }

        // 组装接口请求地址
        $allowed_api_sys = array_keys(SysNameConst::$constsMap);
        if (!in_array('svc_' . $svc, $allowed_api_sys)) {
            biz_exception("{$svc}不是合法的服务标志");
        }

        if (is_laravel()) {
            $rpc_conf = config('rpc');
        } else {
            load_common_conf('api');
            $rpc_conf = is_think5_1() ? config("rpc.") : config('rpc');
        }
        $url = isset($rpc_conf[$svc]) ? $rpc_conf[$svc]['domain'] : $rpc_conf['common']['domain'];
//        $url .= "{$svc}?c={$ctl}";
        $url .= "rpc_server.php?method={$svc}.{$ctl}";
        $time_start = microtime(true);

        $client = new \Hprose\Http\Client($url, false);//[项目名]/[控制器名]/start 启动服务，false表示同步方式调用

        // 请求接口
        $current_error_times = 0;
        $err_output = '';
        $err_code = 1;
        $res = [];
        while ($err_code && $current_error_times < $errorRetryTimes) {
            try {
//      ult = $client->$action();//调用server的方法
                $res = call_user_func_array([$client, $action], $args);
                $err_code = 0;
            } catch (\Exception $e) {
                trace_exception($e);
                $current_error_times++;

                //BizException被hprose拦截，从异常信息里提取code,msg
                $err_output = (str_replace('Wrong Response: ', '', $e->getMessage()));
                $exception_pos = strpos($err_output, 'throwBizException');
                if ($exception_pos) {
                    $err_msg    = trim(substr($err_output, 0, strpos($err_output, "\n#0")));
                    $err_output = substr($err_output, $exception_pos+19, strpos($err_output, "\n#1 ")-$exception_pos+2);
                    $arr = explode(', ', $err_output);
//                    $err_output = rtrim($arr[0], "'");//var_export(['code' => $arr[1], 'msg' => rtrim($arr[0], "'")], 1);
                    $err_output = $err_msg;//var_export(['code' => $arr[1], 'msg' => rtrim($arr[0], "'")], 1);
                    $err_code   = $arr[1];
                }
                if ($current_error_times >= $errorRetryTimes) {
                    break;
                }
            }
        }

        $used_time  = number_format(microtime(true) - $time_start, 4);
        if (defined('LARAVEL_START')) {
            \Illuminate\Support\Facades\Log::alert("【req_bg_rpc】输入数据>>>>>>>{$svc_uri}-{$url}：" .var_export($args,true));
            \Illuminate\Support\Facades\Log::alert("【req_bg_rpc】{$used_time}s 输出数据<<<<<<<{$url}：\r\n" . var_export($res, true));
        } else {
            trace("【req_bg_rpc】输入数据>>>>>>>{$svc_uri}-{$url}：" . var_export($args, true));
            trace("【req_bg_rpc】{$used_time}s 输出数据<<<<<<<{$url}：\r\n" . var_export($res, true));
        }

        $err_data = [
            'url' => $url,
            'param' => $args,
            'used_time' => $used_time,
            'output' => $err_output,
        ];

        // 错误码当做异常处理
        if ($err_output) {
            throw new BizException($err_output, $err_code, $err_data);
        }
        if (is_array($res) && isset($res['e_code'])) {
            throw new BizException($res['msg'], $res['e_code'], $res['data']);
        }
        return $res;
    }

    public static function work($args=null, $caller_depth=1)
    {
	    $called_info = debug_backtrace(null, $caller_depth+1)[$caller_depth];
//        $sub_class_names = explode('\\', get_called_class());
        $sub_class_names = explode('\\', $called_info['class']);
        $sub_class_name = str_replace('Client', '', $sub_class_names[count($sub_class_names) -1]);
        $func    = $called_info['function'];
        $svc_uri = $sub_class_names[1] . '.' . $sub_class_name . '.' . $func;
        $args    = $args ?: $called_info['args'];

        return self::request($svc_uri, $args);
	}
}
