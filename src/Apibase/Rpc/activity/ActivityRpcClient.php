<?php
/**
 * ActivityRpcClient.php
 * 2020-04-07  wangpeng<wangpeng@bookgoal.com.cn>
 */
namespace EasyUtils\Apibase\Rpc\activity;


use EasyUtils\Apibase\Rpc\RpcClient;
use EasyUtils\Apibase\RpcHandler;

/**
 * Class ActivityRpcClient.
 *
 * @property ActiveInterface | RpcHandler        $active
 * @property ActiveUserInterface | RpcHandler    $activeUser
 * @property CommentInterface | RpcHandler       $comment
 */
class ActivityRpcClient extends RpcClient
{
}