<?php
/*
 * 消息消费日志
 *
 * MessageLog.php
 * 2019-04-15 guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\model;

use think\Model;

class MessageLog extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'db_message_log';

    protected $autoWriteTimestamp = false;

    public function __construct($data = [])
    {
        //优先使用自定义的database配置
        $database = config('message.database');
        if ($database) {
            $this->connection = $database;
        }
        parent::__construct($data);
    }

/*
 CREATE TABLE `db_message_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`subject` varchar(32) DEFAULT '0' COMMENT '消息主题',
`batch_id` int(11) NOT NULL COMMENT '批次号，将多个消息归到一批。以便后台查询',
`biz_id` varchar(32) DEFAULT NULL COMMENT '业务id，深度定制业务时用，可省略。如：aid',
`biz_param` varchar(1024) DEFAULT NULL COMMENT '后台展示用的关键参数key-val列表',
`msg_sn` bigint(14) DEFAULT NULL COMMENT '消息唯一序列码',
`create_time` int(10) NOT NULL COMMENT '创建时间',
`err` varchar(512) NOT NULL COMMENT '更新时间',
`res_type` tinyint(1) DEFAULT '0' COMMENT '状态：0 等待添加到消息队列 1发送到消息队列 2 消息正在处理 3 消息处理成功',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
*/

}
