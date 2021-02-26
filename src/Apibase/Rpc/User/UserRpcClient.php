<?php
/*
 * 用户服务调用客户端类
 * 定义可使用的服务模块以及客户端句柄
 *
 * UserRpcClient.php
 * 2020-03-23 guiyj007@gmail.com
 *
 */
namespace EasyUtils\Apibase\Rpc\User;

use EasyUtils\Apibase\Rpc\lms\BookInterface;
use EasyUtils\Apibase\Rpc\lms\BrInterface;
use EasyUtils\Apibase\Rpc\lms\RdInterface;
use EasyUtils\Apibase\Rpc\RpcClient;
use EasyUtils\Apibase\Rpc\User\ReaderCardInterface;
use EasyUtils\Apibase\Rpc\User\ReaderFaceInterface;
use EasyUtils\Apibase\Rpc\User\User3rdInterface;
use EasyUtils\Apibase\Rpc\User\UserInterface;
use EasyUtils\Apibase\Rpc\User\ReaderInterface;
use EasyUtils\Apibase\RpcHandler;
use EasyUtils\Kernel\Traits\SingletonTrait;

/**
 * Class PointRpcClient.
 *
 * @property UserInterface | RpcHandler        $user
 * @property User3rdInterface | RpcHandler        $user3rd
 * @property ReaderInterface | RpcHandler        $reader
 * @property ReaderCardInterface | RpcHandler        $readerCard
 * @property ReaderFaceInterface | RpcHandler        $readerFace
 * @property CustomerInterface | RpcHandler        $customer
 */
class UserRpcClient extends RpcClient
{

}
