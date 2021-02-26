<?php
/*
 * json rpc服务客户端调用的句柄类
 *
 * JsonRpcHandler.php
 *
 * rpc方法调用：依赖初始化rpc服务名，服务模块；最终执行时依赖魔术方法执行调用
 */
namespace EasyUtils\apibase;

use Graze\GuzzleHttp\JsonRpc\Client;
use Graze\GuzzleHttp\JsonRpc\Exception\RequestException;
use Graze\GuzzleHttp\JsonRpc\Message\Response;


/**
 * rpc 调用适配器
 */
class JsonRpcHandler
{
    /**
     * @var Client
     */
    protected $client;
    protected $interfacesClassName;
    protected $host;

    function __construct($interfacesClassName, $svc = "default")
    {
        // 组装接口请求地址
        if (defined('LARAVEL_START')) {
            $config = config('rpc');
        } else {
            load_common_conf('api');
            $config = is_think5_1() ? config("rpc.") : config('rpc');
        }


        if (!is_array($config) || !count($config)) {
            $this->trace(__METHOD__ . "配置错误", var_export([$config, $svc], 1), 'error');
            throw new \Exception("rpc 配置错误");
        }
        $host = isset($config[$svc]) ? $config[$svc]['jsonrpc_host'] : $config['common']['jsonrpc_host'];
        if (empty($host)) {
            $this->trace(__METHOD__ . "配置错误", var_export([$config, $svc], 1), 'error');
            throw new \Exception("rpc [${$svc}] 配置内容缺少部分key");
        }

        // Create the client
        $this->client = Client::factory($host, ['rpc_error' => true]);
        $this->interfacesClassName = $interfacesClassName;
        $this->host = $host;
    }

    /**
     * 封装json-rpc 函数很+方法
     *
     * @param [type] $interfacesClassName
     * @param [type] $name
     * @return void
     */
    private  function getMethod($interfacesClassName, $name)
    {
        $arrays = explode("\\", $interfacesClassName);
        $class = $arrays[count($arrays) - 1];
        $class  = $this->strtolower($class);
        $class = str_replace('_interface', '', $class);
        return  "/{$class}/{$name}";
    }

    function __call($name, $arguments)
    {
        $method = $this->getMethod($this->interfacesClassName, $name);
        $request = $this->client->request(1, $method, $arguments);

        $this->trace(__METHOD__ . ":rpc send" . var_export(['method' => $method, "arguments" => $arguments], true));

        $time_start = microtime(true);
        $res = $err_output = '';
        try {
            /**
             * @var Response
             */
            $response = $this->client->send($request);
            if ($response->getRpcErrorCode()) {
                // 错误
                $this->trace(__METHOD__ . "rpc 返回错误" . var_export($response, 1), 'error');
                return false;
            }
            $res = $response->getRpcResult();
        } catch (RequestException $e) {
            $this->trace(__METHOD__ . "rpc 返回错误" . var_export([$name, $arguments, $e->getMessage()], 1), 'error');
            $err_output = (str_replace('rpc 返回错误 ', '', $e->getMessage()));
        } catch (\Exception $e) {
            trace_exception($e);
            $err_output = (str_replace('Wrong Response: ', '', $e->getMessage()));
        }

        $used_time  = number_format(microtime(true) - $time_start, 4);
        $this->trace("【req_bg_rpc】输入数据>>>>>>>{$this->interfacesClassName}::{$name}-{$this->host}：" . var_export($arguments, true));
        $this->trace("【req_bg_rpc】{$used_time}s 输出数据<<<<<<<{$this->interfacesClassName}::{$name}-{$this->host}：\r\n" .
            var_export($res, true));

        // 错误码当做异常处理
        if ($err_output) {
            biz_exception($err_output);
        }

        return $res;
    }

    /**
     * 转换函数名
     *
     * @param [type] $name
     * @return void
     */
    private function strtolower($name)
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $name));
    }

    private function trace($log, $level = 'info') {
        if (!function_exists('trace')) {
            if (defined('LARAVEL_START')) {
                \Illuminate\Support\Facades\Log::$level($log);
            } else {
                biz_exception('trace function should defined yourself!');
            }
        } else {
            trace($log, $level);
        }
    }

}
