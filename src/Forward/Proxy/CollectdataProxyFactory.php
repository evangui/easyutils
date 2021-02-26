<?php
/*
 * 图书馆前置机模块代理系列(数据收集系列族)工厂类
 *
 * CollectdataProxyFactory.php
 * 2019-09-28  guiyj<guiyj007@gmail.com>
 *
 * - 数据收集（前置机收集数据到云端）具体模块的代理对象的方法
 */
namespace EasyUtils\Forward\Service\Proxy;

use EasyUtils\Forward\Service\AbstractProxyFactory;
use EasyUtils\Forward\Service\Proxy\module\impl_collectdata\BookProxy;
use EasyUtils\Forward\Service\Proxy\module\impl_collectdata\BrProxy;
use EasyUtils\Forward\Service\Proxy\module\impl_collectdata\EntranceProxy;
use EasyUtils\Forward\Service\Proxy\module\impl_collectdata\LibraryProxy;
use EasyUtils\Forward\Service\Proxy\module\impl_collectdata\ToolProxy;

/**
 * 图书馆前置机模块代理系列(通用处理族)工厂类
 */
class CollectdataProxyFactory extends AbstractProxyFactory
{
    protected $proxy_container = [];

    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书借阅 模块代理对象
     *
     * @param integer $aid
     * @return \EasyUtils\Forward\Service\Proxy\module\AbstractBrProxy
     */
    public static function getToolProxy($aid)
    {
        $proxy = ToolProxy::getInstance();
        $proxy->initProxy($aid);
        return $proxy;
    }

    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书馆通用业务代理对象
     *
     * @param integer $aid
     * @return \EasyUtils\Forward\Service\Proxy\module\AbstractBrProxy
     */
    public static function getLibraryProxy($aid)
    {
        $proxy = LibraryProxy::getInstance();
        $proxy->initProxy($aid);
        return $proxy;
    }

    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书借阅 模块代理对象
     *
     * @param integer $aid
     * @return \EasyUtils\Forward\Service\Proxy\module\AbstractBookProxy
     */
    public static function getBookProxy($aid) 
    {
//         $proxy = new BookProxy();
        $proxy = BookProxy::getInstance();
        $proxy->initProxy($aid);
        return $proxy;
    }
    
    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书借阅 模块代理对象
     *
     * @param integer $aid
     * @return \EasyUtils\Forward\Service\Proxy\module\AbstractBrProxy
     */
    public static function getBrProxy($aid) 
    {
        $proxy = BrProxy::getInstance();
        $proxy->initProxy($aid);
        return $proxy;
    }

    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书借阅 模块代理对象
     *
     * @param integer $aid
     * @return EntranceProxy
     */
    public static function getEntranceProxy($aid)
    {
        $proxy = EntranceProxy::getInstance();
        $proxy->initProxy($aid);
        return $proxy;
    }

}