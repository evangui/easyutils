<?php
/*
 * 服务端消费消息队列任务的后台程序(区分subject)
 *
 * ConsumeServer.php
 * 2019-04-19 10:50:00  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\command;

use EasyUtils\Mq\Service\Server;
use think\console\Command;
use think\console\Input;
use think\console\Input\Argument;
use think\console\Output;

ini_set('default_socket_timeout',-1);

class ConsumeServer extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('consume_server');
        // 设置参数
        $this
            ->addArgument('channel', Argument::REQUIRED, "channel")
            ->addArgument('thread_num', Argument::OPTIONAL, "thread num")
            ->setDescription('Send wechat template message');
    }

    protected function execute(Input $input, Output $output)
    {
        $channel            = trim($input->getArgument('channel'));
        $thread_num         = intval($input->getArgument('thread_num'));

        $conf = is_think5_1() ? config('message.') : config('message');
        $all_channel_list   = array_keys($conf);
        if ('all' == $channel) {
            $channel_list = $all_channel_list;
        } else {
            $channel_list = [$channel];
        }

        foreach ($channel_list as $channel) {
            if (in_array($channel, ['trace_log', 'database'])) {
                continue;
            }
            if (!in_array($channel, $all_channel_list)) {
                echo "not assist channel: {$channel}";
                continue;
            }
            //捕获所有异常，以免消费任务自己处理报错中断服务
            try {
                Server::mainConsume($channel, $thread_num);
            } catch (\Exception $e) {

            }

        }

//        $redis = HandlerFactory::redis();
//        $redis->subscribe($channel_list, function($redis, $channel, $msg) {
//            Server::mainConsume($redis, $channel, $msg);
//        });
    }


}
