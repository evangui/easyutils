<?php
/*
 * 批次任务的消息统计
 *
 * MessageBatchStat.php
 * 2019-04-15 guiyj<guiyj007@gmail.com>
 */
namespace EasyUtils\Mq\model;

use think\Model;

class MessageBatchStat extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'db_message_batch_stat';

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
CREATE TABLE `db_message_batch_stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` bigint(20) NOT NULL COMMENT '批次号，将多个消息归到一批。以便后台查询',
  `total` mediumint(7) unsigned DEFAULT '0' COMMENT '消费任务错误次数',
  `succ_cnt` smallint(7) unsigned DEFAULT '0' COMMENT '批次任务总数',
  `err_cnt` smallint(7) unsigned DEFAULT '0' COMMENT '错误记录数',
  `in_queue_cnt` smallint(7) unsigned DEFAULT '0' COMMENT '队列待处理任务数',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '更新时间',
  `finished` tinyint(1) DEFAULT '0' COMMENT '批次任务是否已完成',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75680 DEFAULT CHARSET=utf8 COMMENT='批次任务的消息统计表';


*/

}
