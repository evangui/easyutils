<?php
/*
 * 图书管理系统服务调用客户端类
 * 定义可使用的服务模块以及客户端句柄
 *
 * LmsRpcClient.php
 * 2020-03-23 guiyj007@gmail.com
 *
 */
namespace EasyUtils\Apibase\Rpc;

use EasyUtils\Apibase\JsonRpcHandler;
use EasyUtils\Apibase\RpcHandler;
use EasyUtils\Kernel\traits\SingletonTrait;

class RpcClient
{
    /**
     * @var self
     */
    protected static $children;

    protected $svcName = '';
    protected $rpcConfig = [];

    protected function getHandler($ctl, $rpc_config) {
        $class_arr = explode('\\', get_called_class());
        $this->svcName = strtolower(str_replace('RpcClient', '', $class_arr[count($class_arr) - 1]));
        if (!$this->svcName) {
            biz_exception('$this->svcName不能为空');
        }
        if (isset($rpc_config['protocol']) && 'jsonrpc' == $rpc_config['protocol']) {
            $interface_class = "\\EasyUtils\\Apibase\\Rpc\\{$this->svcName}\\" . ucfirst($ctl) . 'Interface';
            $handler = new JsonRpcHandler($interface_class, $this->svcName);
        } else {
            $handler = new RpcHandler();
            $handler->init($this->svcName, $ctl, $rpc_config);
        }

        return $handler;
    }

    /**
     * @return self
     */
    public static function getInstance($rpc_config=[])
    {
        $called_class = get_called_class();
        $key = md5($called_class . serialize($rpc_config));
        if (empty(self::$children[$key])) {
            self::$children[$key] = new $called_class($rpc_config);
        }
        return self::$children[$key];
    }

    /**
     * Dynamically pass methods to the application.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __get($name)
    {
        return self::getHandler($name, $this->rpcConfig);
    }

    /**
     * LmsRpcClient constructor.
     * @param array $rpc_config
     */
    public function __construct($rpc_config=[]) {
        $this->rpcConfig = $rpc_config;
    }
}
