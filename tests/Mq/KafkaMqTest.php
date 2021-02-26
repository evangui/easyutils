<?php
/**
 * Created by PhpStorm.
 * User: guiyajun
 * Date: 2018/9/8
 * Time: 23:54
 */
namespace EasyUtils\message\tests;
use app\api\logic\CirLogLogic;
use app\uar\logic\Reader;
use app\uar\logic\ReaderFace;
use EasyUtils\Kernel\constant\CirlogTypeConst;
use EasyUtils\Kernel\helper\staticUtil\Image;
use EasyUtils\message\Service\KafkaMq;
use EasyUtils\message\Service\SimpleServer;
use PHPUnit\Framework\TestCase;

class KafkaMqTest extends TestCase
{
    public function testProduceMessage()
    {
        $message = [
            'url' => 'xxxx',
            'rd' => "3002_201852",
            't' => time(),
        ];
        $message = [

        ];

        //发送消息到消息队列
        $res = KafkaMq::produceMessage('Borrowreturn_do_3023', [3023, CirlogTypeConst::LOG_TYPE_BORROW_BOOK , time(), 'title']);
        ve($res);
        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }

    public function testConsume()
    {
//        $res = KafkaMq::mainConsume('ReaderFace_bind');
        $res = KafkaMq::consumeOneMessage('Borrowreturn_do_3023');
        ve($res);
        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }


}