<?php
/*
 * CompensateTask 消息发送补偿任务
 *
 * ProduceCompensate.php
 * 2019-04-19 10:50:00  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\command;

use EasyUtils\Mq\Service\Producer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('default_socket_timeout',-1);

class ProduceCompensate extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('produce_compensate');
        // 设置参数
        $this->setDescription('Send wechat template message');
    }

    protected function execute(Input $input, Output $output)
    {
        Producer::compensate();
        Producer::flushMessage();
    }

}
