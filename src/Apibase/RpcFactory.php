<?php
/*
 * rpc服务调用的句柄工厂类
 *
 * RpcFactory.php
 * 2020-03-23 guiyj007@gmail.com
 *
 */
namespace EasyUtils\apibase;

use EasyUtils\Apibase\Rpc\activity\ActivityRpcClient;
use EasyUtils\Apibase\Rpc\article\ArticleRpcClient;
use EasyUtils\Apibase\Rpc\content\ContentRpcClient;
use EasyUtils\Apibase\Rpc\lms\LmsRpcClient;
use EasyUtils\Apibase\Rpc\Point\PointRpcClient;
use EasyUtils\Apibase\Rpc\RpcClient;
use EasyUtils\Apibase\Rpc\User\UserRpcClient;
use EasyUtils\Apibase\Rpc\User\ReaderCardInterface;

/**
 * Class Factory.
 *
 * 用户中心
 * @method static UserRpcClient        user($protocol='', array $rpc_config=[])
 * 积分中心
 * @method static PointRpcClient        point($protocol='', array $rpc_config=[])
 * 活动中心
 * @method static ActivityRpcClient     activity($protocol='', array $rpc_config=[])
 * 内容中心
 * @method static ContentRpcClient     content($protocol='', array $rpc_config=[])
 */
class RpcFactory
{
    /**
     * @param string $name
     * @param array  $config
     *
     * @return RpcClient
     */
    public static function make($name, $protocol='', array $config=[])
    {
        $namespace = ucfirst($name) . 'RpcClient';
        $application = "\\EasyUtils\Apibase\\Rpc\\{$name}\\$namespace";
        $config['protocol'] = $protocol;
//        return new $application($config);
        return $application::getInstance($config);
    }

    /**
     * 获取用户服务的ReaderCard模块rpc句柄
     * @param array $config
     * @return ReaderCardInterface| RpcHandler
     */
    public static function userReaderCard($rpc_config=[]) {
        return self::getHandler(__FUNCTION__, $rpc_config);
    }


    private static function getHandler($funcName, $rpc_config) {
        $arr = explode('_', uncamelize($funcName));
        $app = $arr[0];
        unset($arr[0]);
        $ctl = '';
        foreach ($arr as $word) {
            $ctl .= ucfirst($word);
        }
        $handler = new RpcHandler();
        $handler->init($app, $ctl, $rpc_config);
        return $handler;
    }

    /**
     * Dynamically pass methods to the application.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::make($name, ...$arguments);
    }
}
