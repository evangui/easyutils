<?php
/*
 * 图书馆前置机模块代理系列(通用处理族)工厂类
 *
 * GeneralProxyFactory.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * - 实现可生成的通用具体模块的代理对象的方法
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy;

use EasyUtils\CustomBiz\Forward\Service\AbstractProxyFactory;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general\BookProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general\BrProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general\EntranceProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general\LibraryProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general\ToolProxy;

/**
 * 图书馆前置机模块代理系列(通用处理族)工厂类
 */
class GeneralProxyFactory extends AbstractProxyFactory
{
    protected $proxy_container = [];

    /**
     * 根据图书馆id，获取对应图书馆的前置机 图书借阅 模块代理对象
     *
     * @param integer $aid
     * @return \EasyUtils\CustomBiz\Forward\Service\Proxy\module\AbstractBrProxy
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
     * @return \EasyUtils\CustomBiz\Forward\Service\Proxy\module\AbstractBrProxy
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
     * @return \EasyUtils\CustomBiz\Forward\Service\Proxy\module\AbstractBookProxy
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
     * @return \EasyUtils\CustomBiz\Forward\Service\Proxy\module\AbstractBrProxy
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