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
use EasyUtils\Kernel\helper\staticUtil\Image;
use EasyUtils\message\Service\KafkaMq;
use EasyUtils\message\Service\SimpleMq;
use EasyUtils\message\Service\SimpleServer;
use PHPUnit\Framework\TestCase;

class SimpleMqTest extends TestCase
{
    public function testProduceMessage()
    {
        $message = [
            'aid' => '3003',
            'device_id' => "3002_201852",
            'command' => "open_door",
            't' => time(),
        ];
v($message);
        //发送消息到消息队列
        $res = SimpleMq::produceMessage('Door_command', json_encode($message));
        ve($res);

        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }

    public function testAsyncCall()
    {
        //发送消息到消息队列
        $data = [
            'rdid' => 3243242,
            'type' => 1
        ];
        $res = SimpleMq::asyncCall('test', $data, 2);
        ve($res);

        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }

    public function testConsume()
    {
//        $res = SimpleMq::consumeOneMessage('Door_command');
//        $res = SimpleMq::consumeOneMessage('Borrowreturn_do');
        $res = SimpleMq::consumeOneMessage('asyncCall-test');
        ve($res);
        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }

    public function testMainConsume()
    {
        $channel = 'all';
        $start = microtime(true);
        $res = SimpleMq::mainConsume($channel);
        v($res);
    }


}