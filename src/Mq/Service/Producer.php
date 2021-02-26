<?php
/*
 * 业务消息生产发起端
 *
 * Producer.php
 * 2019-04-19 10:50:00  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\Service;

use EasyUtils\Kernel\Constant\ApiCodeConst;
use EasyUtils\Kernel\exception\BizException;
use think\Db;

/**
 *  业务消息生产发起端
 */
class Producer
{
    //消息主题
    public $subject;

    //【可选项】业务id，深度定制业务时用，可省略。如：aid
    public $bizId;

    //用于后台展示的关键参数（key-val列表）
    public $bizParam;

    //成功记录保留天数
    public $holdDay;

    //批次号，将多个消息归到一批。以便后台查询
    public $batchId;

    //消费回调函数/方法。尽量不另外自定义，用默认的传空即可
    public $consumerCallbackFunc;

    //消费回调参数
    public $consumerCallbackParam;

    public $producerCallbackFunc;
    public $producerCallbackParam;

    /**
     * $consumer_callback_func 严禁用exit等中断程序的语句
     */
    public  function __construct(
        $consumer_callback_func,
        $consumer_callback_param,
        $subject,
        $biz_id,
        $biz_param,
        $hold_day = 0,
        $batch_id=0
    ) {
        //@todo: 过滤拦截无效的方法名 $consumer_callback_func
        $this->consumerCallbackFunc  = $consumer_callback_func;
        $this->consumerCallbackParam = $consumer_callback_param;
        $this->subject               = $subject;
        $this->bizId                 = $biz_id;
        $this->bizParam              = is_array($biz_param) ? json_encode($biz_param) : $biz_param;
        $this->holdDay               = $hold_day;
        $this->batchId               = $batch_id;
    }

    /**
     * 发送消息
     * $producer_callback [$this, 'func']
     */
    public function sendMessage($producer_callback_func='', $producer_callback_param='')
    {
        $this->producerCallbackFunc  = $producer_callback_func;
        $this->producerCallbackParam = $producer_callback_param;
        return $this->produce();
    }

    /**
     * 业务消息开始发起生产，添加到消息队列
     */
    public function produce()
    {
        $this->beginTx();
        if (!$this->doBizWork()) {
            return false;
        }
        $message_sn = $this->insertDbMessage();
        $this->endTx($message_sn);
        if (!$message_sn) {
            throw new BizException('db message生成失败', ApiCodeConst::DB_ERR);
        }
        return $this->produceWithoutBizWork($message_sn);
    }

    /**
     * 生产消息并将消息发送到消息队列（不再进行业务操作与db message的事务一致性处理）
     *
     * 可用于客户端不想通过 producer_callback_func 进行回调执行，可模仿produce方法调用本方法
     *
     * @param $message_sn
     * @return bool|void
     */
    public function produceWithoutBizWork($message_sn)
    {
        if (!$message_sn) {
            throw new BizException('message_sn不能为空');
        }

        $subject  = $this->subject;
        // 发送消息到服务端
        $send_res = $this->sendMessageToServer($message_sn);

        // 实时err ，网络err=>pass , 等待补偿扫码任务执行：CompensateTask.scanMessage()
        if (!$send_res) {
            return ;
        }

        $status_res = self::updateDbMessageStatus($subject, $message_sn, DbMessage::MSG_STATUS_IN_QUEUE);
        return $status_res;
    }


    /**
     * 开启DB事务
     */
    public function beginTx()
    {
        if (!$this->producerCallbackFunc) {
            return;
        }
        Db::startTrans();
    }

    /**
     * 结束DB事务（根据$result结果 commit或rollback）
     */
    public function endTx($result)
    {
        if (!$this->producerCallbackFunc) {
            return;
        }
        $result ? Db::commit() : Db::rollback();
    }

    /**
     * 执行本业务操作
     */
    public function doBizWork()
    {
        $callback = $this->producerCallbackFunc;
        $param    = $this->producerCallbackParam;
        if (!$callback) {
            return true;
        }
        call_user_func_array($callback, $param);
        return true;
    }

    /**
     * 插入数据库消息记录表（按subject分表）
     */
    public function insertDbMessage()
    {
        $time = time();
        $data = [
//            'msg_sn'         => generate_id(),
            'batch_id'       => $this->batchId,
            'biz_id'         => $this->bizId,
            'biz_param'      => $this->bizParam,
            'item_hold_day'  => $this->holdDay,
            'create_time'    => $time,
            'update_time'    => $time,
        ];

        $res = DbMessage::insertDbMessage($this->subject, $data);
        return $res;
        return $res ? $data['msg_sn'] : '';
    }

    /**
     * 发送消息到服务端
     */
    private function sendMessageToServer($message_sn)
    {
        return Server::appendMessage(
            $this->subject,
            $message_sn,
            $this->consumerCallbackFunc,
            $this->consumerCallbackParam
        );
    }

    /**
     * 更新db message状态
     */
    public function  updateDbMessageStatus($subject, $msg_sn, $status)
    {
        return DbMessage::updateStatus($subject, $msg_sn, $status);
    }

    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * 消息发送补偿任务
     * @param array $subjects   补偿主题数组，如果传空，则遍历处理所有主题队列
     */
    public static function compensate($subjects = [])
    {
        //@todo: 状态为wait不在消息队列的，补偿进入队列
        $where = [
            'status' => DbMessage::MSG_STATUS_SEND_QUEUE_WAIT
        ];

        if (!$subjects) {
            $conf = is_think5_1() ? config('message.') : config('message');
            $subjects = array_keys($conf);
        } else {
            is_string($subjects) && $subjects = explode(',', $subjects);
        }

        $model = model('\EasyUtils\Mq\model\DbMessage');

        foreach ($subjects as $subject) {
            if (in_array($subject, ['trace_log', 'database'])) {
                continue;
            }
            $item_list = $model->selectItem($subject, $where);
            foreach ($item_list as $item) {
                $res = Server::appendMessageFromCache($subject, $item['id']);
            }
        }
    }

    /**
     * 消息发送补偿任务
     * @param array $subjects   补偿主题数组，如果传空，则遍历处理所有主题队列
     */
    public static function flushMessage($subjects = [])
    {
        if (!$subjects) {
            $conf = is_think5_1() ? config('message.') : config('message');
            $subjects = array_keys($conf);
        } else {
            is_string($subjects) && $subjects = explode(',', $subjects);
        }

        $model = model('\EasyUtils\Mq\model\DbMessage');

        foreach ($subjects as $subject) {
            if (in_array($subject, ['trace_log', 'database'])) {
                continue;
            }

            $sql = "
              DELETE from db_message_{$subject}
              WHERE update_time<UNIX_TIMESTAMP()-84600*item_hold_day
            ";
            $res = $model->execute($sql);
            echo "delete {$res} items";
        }
    }
}
