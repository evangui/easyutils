<?php
/*
 * yar rpc服务端控制器
 *
 * YarController.php
 *
 * 本公用控制器主要用来定义请求与响应的公用助手方法
 */
namespace EasyUtils\Kernel\controller;

/**
 * Yar控制器类
 */
abstract class RpcController
{
    public function index()
    {
        $app    = request()->module();
        $ctl    = input('get.c', '');
        $ctl = ucfirst($ctl);
        $rpc_class = "\\app\\{$app}\\Rpc\\{$ctl}Rpc";
        if (!class_exists($rpc_class)) {
            exit('403');
        }
        (new $rpc_class())->start();
        exit();
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
