<?php
/**
 * Created by PhpStorm.
 * User: guiyajun
 * Date: 2018/9/8
 * Time: 23:54
 */
namespace EasyUtils\message\tests;
use EasyUtils\message\Service\Consumer;
use EasyUtils\message\Service\SimpleServer;
use EasyUtils\Wechat\Service\We;
use PHPUnit\Framework\TestCase;

class DefaultMqTest extends TestCase
{
    public function testConsumer()
    {
        Consumer::processNotInQueueMessage('we_message');
        die;
    }

    public function testSend()
    {

//        $redis = HandlerFactory::redis();
//        v($redis->publish('chan-1', 'ssss'));

        $res = We::queueTemplateMessage(3007, 3, [], 'o3pzEjrjdpCQNj6VnBCoA8I-5GkY');
        v($res);


        $consumer_callback_func = '\app\test\logic\User::msgCallback';
        $consumer_callback_param = [$aid=12, [11, 22]];
        $subject = 'we_message';
        $biz_id = '12';
        $biz_param = $consumer_callback_param;
        $hold_day = 30;
        $batch_id = 0;

        $producer = new Producer(
            $consumer_callback_func,
            $consumer_callback_param,
            $subject,
            $biz_id,
            $biz_param,
            $hold_day = 0,
            $batch_id=0
        );

        $producer_callback_func = [];
        $producer_callback_param = [];
        $res = $producer->sendMessage($producer_callback_func, $producer_callback_param);
        v($res);
    }

    public function scan()
    {
        CompensateTask::scanNotinQueueMessages();
    }

    public function comsumeTask()
    {
//        $redis = HandlerFactory::redis();
//        $redis->subscribe(['we_message'], __CLASS__ . '::comsume_callback');
//

        Server::mainConsume('we_message');
    }

    public function messageServer()
    {
//        v(HandlerFactory::redis()->zRange('we_message', 0, -1));
        v(HandlerFactory::redis()->lRange('we_message', 0, 10), 0);
//        die;
        Server::consumeOneMessage('we_message');
    }

    public function stat()
    {
        $data = Server::statTaskByBatchIdFromStatTable('we_message', 120002);
        v($data);
    }


}