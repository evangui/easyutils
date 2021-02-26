<?php
/*
 * 服务端消费消息队列任务的后台程序(区分subject)
 *
 * (简单消费模式)
 * - 不操作数据库维护数据一致性
 * - 不记录数据库消费日志
 *
 * SimpleConsumeServer.php
 * 2019-04-19 10:50:00  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\command;

use EasyUtils\Mq\Service\KafkaMq;
use think\console\Command;
use think\console\Input;
use think\console\Input\Argument;
use think\console\Output;

ini_set('default_socket_timeout',-1);

class KafkaConsumeServer extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('kafka_consume_server');
        // 设置参数
        $this
            ->addArgument('channel', Argument::REQUIRED, "channel")
            ->setDescription('kafka consume queue message ');
    }

    protected function execute(Input $input, Output $output)
    {
        $channel = trim($input->getArgument('channel'));
        $start = microtime(true);
        KafkaMq::mainConsume($channel);
        $output->info("done! --> use time:". (microtime(true) - $start));
    }


}
