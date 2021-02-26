<?php
namespace EasyUtils\Apibase\Rpc\content;


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
 * Class ContentRpcClient.
 *
 * @property ArticleInterface | RpcHandler        $article
 * @property ArticleCatInterface | RpcHandler     $articleCat
 */
class ContentRpcClient extends RpcClient
{

}
