<?php
/*
 * 图书馆前置机代理工厂抽象类
 *
 * ForwardTest.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 家族工厂的抽象定义。
 * - 定义可生成的系列具体模块代理对象的方法
 * - 保持所有系列生成的代理模块对象方式的接口一致
 * - 目前仅支持general一个系列。见 GeneralProxyFactory
 */
namespace EasyUtils\CustomBiz\Forward;

use EasyUtils\CustomBiz\Forward\Service\Proxy\module\AbstractBookProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\AbstractBrProxy;

/**
 * 图书馆前置机代理工厂抽象类
 */
class AbstractProxyFactory
{
    /**
     * 根据图书馆id，获取对应图书馆的前置机图书模块代理对象
     *
     * @param integer $aid
     * @return AbstractBookProxy
     */
    public static function getBookProxy($aid) 
    {
    }
    
    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书借阅 模块代理对象
     *
     * @param integer $aid
     * @return AbstractBrProxy
     */
    public static function getBrProxy($aid) 
    {
    }
}