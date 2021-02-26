<?php
/**
 *
 * Created by PhpStorm.
 * User: guiyajun
 * Date: 2020/11/17
 * Time: 12:54
 */
namespace EasyUtils\Apibase\tests;
use EasyUtils\Apibase\RpcFactory;
use PHPUnit\Framework\TestCase;

class ReaderCardTest extends TestCase
{
    public function testListTypes()
    {
        //hprose rpc调用
        $res = RpcFactory::user()->readerCard->listTypes(2, 0);
        ve($res[0], 0);

        //json rpc调用
        $res = RpcFactory::user('jsonrpc')->customer->findAdminUser(30);
        ve($res, 0);

        $this->assertEquals(true, $res['id'] > 0);
    }


}