<?php
/*
 * 与消息队列任务 强关联的DB消息服务
 *
 * DbMessage.php
 * 2019-04-15 guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\Service;

use think\Db;

class DbMessage
{
    /**
     * db消息状态
     * 0 等待添加到消息队列 1发送到消息队列 2 消息正在处理 3 消息处理成功 4 消息处理失败
     */
    const MSG_STATUS_SEND_QUEUE_WAIT    = 0;
    const MSG_STATUS_IN_QUEUE           = 1 ;
    const MSG_STATUS_QUEUE_PROCESS_ING  = 2 ;
    const MSG_STATUS_QUEUE_PROCESS_OK   = 3 ;
    const MSG_STATUS_QUEUE_PROCESS_ERR  = 4 ;

    public static function getModel()
    {
        return model('\EasyUtils\Mq\model\DbMessage');
    }

    /**
     * 插入db数据库消息记录表（按subject分表）
     * @param $subject
     * @param $data
     * @return int|string
     */
    public static function insertDbMessage($subject, $data)
    {
        $model = self::getModel();
        $res = $model->initTable($subject);
        return $model->insertItem($subject, $data);
    }

    /**
     * 更新状态
     * @param $subject
     * @param $msg_sn
     * @param $status
     * @return mixed
     */
    public static function updateStatus($subject, $msg_sn, $status)
    {
        $where = ['id' => $msg_sn];
        $data = [
            'status' => $status,
            'update_time' => time(),
        ];
        //记录错误次数
        if (self::MSG_STATUS_QUEUE_PROCESS_ERR == $status) {
            $data['consume_err_times'] = Db::raw('consume_err_times+1');
        }

        return model('\EasyUtils\Mq\model\DbMessage')->updateItem($subject, $where, $data);
    }

    public static function update($subject, $where, $data)
    {
        return model('\EasyUtils\Mq\model\DbMessage')->updateItem($subject, $where, $data);
    }

    public static function find($subject, $where)
    {
        return model('\EasyUtils\Mq\model\DbMessage')->findItem($subject, $where);
    }

    public static function select($subject, $where)
    {
        return model('\EasyUtils\Mq\model\DbMessage')->selectItem($subject, $where);
    }

    public static function count($subject, $where)
    {
        return model('\EasyUtils\Mq\model\DbMessage')->countItem($subject, $where);
    }
}
