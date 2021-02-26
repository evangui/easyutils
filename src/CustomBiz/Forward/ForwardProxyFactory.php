<?php
/*
 * 图书馆前置机代理模块工厂类
 *
 * ForwardProxyFactory.php
 * 2019-01-29 11:41  guiyj<guiyj007@gmail.com>
 *
 * 用于获取 前置机模块代理的执行对象
 * 
 * ## 使用示例：
 *  $book_proxy = ForwardProxyFactory::getBookProxy(12);
 *  $res = $book_proxy->getPositionByBarcodes('000649890,000649891');
 */
namespace EasyUtils\CustomBiz\Forward;

use EasyUtils\CustomBiz\Forward\Service\Proxy\CollectdataProxyFactory;
use EasyUtils\CustomBiz\Forward\Service\Proxy\GeneralProxyFactory;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IBookProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IBrProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IEntranceProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\ILibraryProxy;

//tp5.1版本前后的兼容处理
if (defined('THINK_VERSION')) {
    \think\Config::load(__DIR__ . '/../config.php');
} else {
    \think\facade\Config::load(__DIR__ . '/../config.php');
}


/**
 * 图书馆前置机代理模块工厂类
 */
class ForwardProxyFactory
{
    /**
     * 当前支持的前置机代理模块
     * @var array
     */
    private static $allowedModules = ['library', 'tool', 'book', 'br', 'entrance'];
//     private static $allowedFamalies = ['general'];
    
    /**
     * 具体模块代理容器
     * @var array
     */
    private static $proxyMap = [];

    /**
     * 获取图书模块的前置机代理对象
     *
     * @param integer $aid
     * @throws ForwordProxyException
     * @return mixed|\EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general\ToolProxy
     */
    public static function getToolProxy($aid, $method_name='')
    {
        $module_name = 'tool';
        return self::getProxy($aid, $module_name);
    }

    /**
     * 获取图书模块的前置机代理对象
     * 
     * @param integer $aid
     * @throws ForwordProxyException
     * @return IBookProxy
     */
    public static function getBookProxy($aid, $method_name='')
    {
        $module_name = 'book';
        return self::getProxy($aid, $module_name);
    }
    
    /**
     * 获取图书借阅模块的前置机代理对象
     *
     * @param integer $aid
     * @throws ForwordProxyException
     * @return IBrProxy
     */
    public static function getBrProxy($aid, $method_name='')
    {
        $module_name = 'br';
        return self::getProxy($aid, $module_name, $method_name);
    }

    /**
     * 获取门禁模块的前置机代理对象
     *
     * @param integer $aid
     * @throws ForwordProxyException
     * @return IEntranceProxy
     */
    public static function getEntranceProxy($aid, $method_name='')
    {
        $module_name = 'entrance';
        return self::getProxy($aid, $module_name, $method_name);
    }

    /**
     * 获取门禁模块的前置机代理对象
     *
     * @param integer $aid
     * @throws ForwordProxyException
     * @return ILibraryProxy
     */
    public static function getLibraryProxy($aid, $method_name='')
    {
        $module_name = 'library';
        return self::getProxy($aid, $module_name, $method_name);
    }

    /**
     * 根据图书馆id和模块名，获取代理服务模块的代理对象
     *
     * @param integer $aid
     * @param string $module_name
     */
    private static function getProxy($aid, $module_name, $method_name='')
    {
        $key = "{$aid}-{$module_name}-{$method_name}";
        if (!empty(self::$proxyMap[$key])) {
            return self::$proxyMap[$key];
        }
        
        //根据aid识别处理请求家族。默认用general
        $family = self::getFamilyName($aid, $module_name, $method_name);

        if (!in_array($module_name, self::$allowedModules)) {
            throw new ForwordProxyException('暂不支持该模块');
        }
        
        //
        // 从家族工厂获取请求模块实例
        //
        switch ($family) {
            case 'general':
//                 $proxy = GeneralProxyFactory::getBookProxy($aid);
                $method_name = 'get' . ucfirst($module_name) . 'Proxy';
                $proxy = GeneralProxyFactory::$method_name($aid);
                break;
            case 'collectdata':
//                 $proxy = GeneralProxyFactory::getBookProxy($aid);
                $method_name = 'get' . ucfirst($module_name) . 'Proxy';
                $proxy = CollectdataProxyFactory::$method_name($aid);
                break;
            default:
                break;
        }
        
        self::$proxyMap[$key] = $proxy;
        return $proxy;
    }

    /**
     * 根据aid、模块名，获取对应的接口系列家族名
     * @param $aid
     * @param $module_name
     * @return string
     */
    private static function getFamilyName($aid, $module_name, $method_name='')
    {
        $forword_proxy_conf = config('forword_proxy.' . $aid);
        if (!isset($forword_proxy_conf['api_family'])) {
            return strtolower('general');
        }

        //优先使用模块+方法指定的 家族
        if ($method_name && isset($forword_proxy_conf['api_family']["{$module_name}.{$method_name}"])) {
            return $forword_proxy_conf['api_family']["{$module_name}.{$method_name}"];
        }
        //再次 优先使用模块指定的 家族
        if (isset($forword_proxy_conf['api_family'][$module_name])) {
            return strtolower($forword_proxy_conf['api_family'][$module_name]);
        } elseif (isset($forword_proxy_conf['api_family']['all'])) {
            //再次 优先使用通用的 家族
            return strtolower($forword_proxy_conf['api_family']['all']);
        } else {
            //都不存在，指定固定的general为默认处理家族
            return strtolower('general');
        }

    }
    
    /**
     * 根据图书馆id，获取对应图书馆的前置机总代理对象
     * 
     * @param integer $aid
     */
    private static function getProxy2($aid, $module_name) 
    {
        $key = $aid . '-' . $module_name;
        if (!empty(self::$proxyMap[$key])) {
            return self::$proxyMap[$key];
        }
        
        //根据aid识别处理请求家族。默认用general
        $family = strtolower('general');    //目前写死，后面改用aid-family配置方式
        
        //从家族工厂获取请求模块实例
        if (!in_array($module_name, self::$allowedModules)) {
            throw new ForwordProxyException('暂不支持该模块');
        }
        
        $class_namespace_path = "\EasyUtils\\Forward\\Service\\Proxy\\module\\impl_{$family}\\" . ucfirst($module_name);
//         $class_namespace_path = "\EasyUtils\\Forward\\Service\\Proxy\\{$family}ProxyFactory";
        $instance = (new \ReflectionClass($class_namespace_path))->newInstance();
        $proxy = $instance->initProxy($aid);
        self::$proxyMap[$key] = $proxy;
        return $proxy;        
    } 
}
