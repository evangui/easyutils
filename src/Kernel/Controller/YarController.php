<?php
/*
 * yar rpc服务端控制器
 *
 * YarController.php
 *
 * 本公用控制器主要用来定义请求与响应的公用助手方法
 */
namespace EasyUtils\Kernel\Controller;

/**
 * Yar控制器类
 */
abstract class YarController
{

    /**
     * 构造函数
     * @access public
     */
    public function __construct()
    {
        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }

        //判断扩展是否存在
        if (!extension_loaded('yar')) {
            throw new \Exception('not support yar');
        }

        //实例化Yar_Server
        $server = new \Yar_Server($this);
        // 启动server
        $server->handle();
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
