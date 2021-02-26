<?php
/*
 * 服务端调用的消费者服务
 * 主要用于调度具体的消费者处理任务，并跟踪消费情况
 *
 * Consumer.php
 * 2019-04-25 guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\Service;

use EasyUtils\Mq\model\MessageLog;

class Consumer
{
    //消费任务错误次数最大值
    const MAX_ERROR_RETRY_TIMES = 6;

    static $dbMessageLogList = [];
    static $fileLogList = [];

    /**
     * 由服务端唤起单个任务进行消费调用
     * @param $channel
     * @param $message
     * @return bool
     */
    public static function invokeTask($channel, $message){
        $trace_log = config('message.trace_log');

        /**
         * 1 参数验证
         */
        $msg_arr = json_decode($message, true);
        $trace_log && trace('invokeTask:[info]:$msg_arr='. var_export($msg_arr, 1), 'mq');
        if (!$msg_arr || count($msg_arr) < 3) {
            $trace_log && trace("invokeTask:[error]:invalid msg - {$message}", 'mq');
            return false;
        }

        /**
         * 2 数据解析&消费回调方法解析
         */
        list($message_sn, $callback_func, $callback_param) = $msg_arr;
        if (!$callback_func) {
            $config = config("message.{$channel}");
            if (!is_array($config)) {
                self::processErrMessage($channel, $message, $message_sn);
                return false;
            }
            
                $trace_log && trace("invokeTask:[info]: {$channel} \$config=" . var_export($config, 1), 'mq');
            if (empty($config['callback'])) {
                //@todo: 处理未指定回调消费者异常
                $trace_log && trace('invokeTask:[error]: empty callback', 'mq');
                self::processErrMessage($channel, $message, $message_sn);
                return false;
            }
            $callback_func = $config['callback'];
        }

        /**
         * 3 获取db message，并验证有效状态
         */
        $db_msg = DbMessage::find($channel, ['id' => $message_sn]);
        Server::setLastDbAliveTime();
        $db_msg && $db_msg = $db_msg->toArray();
        if (empty($db_msg)) {
            $trace_log && trace('invokeTask:[info]: $db_msg为空', 'mq');
            return false;
        }
        $trace_log && trace('invokeTask:[info]: $db_msg=' . var_export($db_msg, 1), 'mq');

        //判断记录状态
        $valid_status = [
            DbMessage::MSG_STATUS_SEND_QUEUE_WAIT,  //消费任务处理快时，存在db记录等待加入队列的，但实际已到队列。当做可正常消费
            DbMessage::MSG_STATUS_IN_QUEUE,
            DbMessage::MSG_STATUS_QUEUE_PROCESS_ERR
        ];
        if (!in_array($db_msg['status'], $valid_status)) {
            $trace_log && trace('invokeTask:[error]: invalid db status', 'mq');
            return false;
        }

        //错误次数超过次数，丢掉消息，记录问题，人为跟进
        $max_err_times = config("message.{$channel}")['max_error_times'];
        if ($db_msg['consume_err_times'] >= $max_err_times) {
            trace('invokeTask:[error]: consume_err_times超过规定次数', 'mq');
            return false;
        }

        /**
         * 4 执行消费任务
         */
        $res = $err = '';
        try {
            $trace_log && trace("invokeTask:[info]: {$callback_func},".var_export($callback_param,1), 'mq');
            $res = call_user_func_array($callback_func, $callback_param);
//            $res = rand(0,1);   //debug
        } catch (\Exception $e) {
//            throw $e; // for debug
            $err = $e->getMessage();
            $trace_log && trace("invokeTask:[callback error]: {$e->getCode()}|{$err}", 'mq');
        }

        /**
         * 5 消费任务成功后。记录状态，记录日志
         */
        //response err&超时err: rePushQueue() 重新加入队列，及记录错误重试次数
        (!$res || $err) && $err_res = self::processErrMessage($channel, $message, $message_sn);
        self::writeConsumeLog($channel, $db_msg, $res, $err);

        if ($res) {
            self::processSuccessMessage($channel, $message_sn);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 消费任务成功后的处理 response ok
     *
     * @param $channel
     * @param $message
     */
    public static function processSuccessMessage($channel, $message_sn)
    {
        config('message.trace_log') && trace("processSuccessMessage:[info]: {$channel},{$message_sn}", 'mq');

        //改状态
        $status_res = DbMessage::updateStatus($channel, $message_sn, DbMessage::MSG_STATUS_QUEUE_PROCESS_OK);
        return $status_res;
    }

    /**
     * response err&超时err: rePushQueue() 重新加入队列，及记录错误重试次数
     *
     * @param $channel
     * @param $message
     */
    public static function processErrMessage($channel, $message, $message_sn)
    {
        config('message.trace_log') && trace("processErrMessage:[info]: {$channel},{$message},{$message_sn}", 'mq');

        //再次加入到消息队列
        $err_res = Server::reAppendMessage($channel, $message);
        return DbMessage::updateStatus($channel, $message_sn, DbMessage::MSG_STATUS_QUEUE_PROCESS_ERR);
    }


    /**
     * 写文件日志
     * @param $channel
     * @param $db_msg
     * @param $res
     * @param string $err
     */
    public static function writeConsumeLog($channel, $db_msg, $res, $err='')
    {
        //通用log记录（加db log）
        $log_data = [
            'subject'   => $channel,
            'msg_sn'    => $db_msg['id'],
            'batch_id'  => $db_msg['batch_id'],
            'biz_id'    => $db_msg['biz_id'],
            'biz_param' => $db_msg['biz_param'],
            'create_time' => time(),
            'res_type'  => $res ? 1 : 2,
            'err'       => $err,
        ];
//        MessageLog::create($log_data);
        array_push(self::$dbMessageLogList, $log_data);
        if (count(self::$dbMessageLogList) > 20) {
            MessageLog::insertAll(self::$dbMessageLogList, false);
            self::$dbMessageLogList = [];
        }

        //  文件log
        self::fileLog($channel, $db_msg['biz_id'], $res, $err);
    }

    protected static function fileLog($channel, $biz_id, $res, $err)
    {
        $dir = env('runtime_path') . "log/msg/";
        !file_exists($dir) && mkdir($dir, 0755, true);
        $file = $dir . "{$channel}" . date('Ymd') . ".log";
        $log = '[' . date('Y-m-d H:i:s') . '] ' . "{$biz_id}-->" . ($res ? 'success' : 'fail');
        !$res && $log .= ",{$err}";

        array_push(self::$fileLogList, $log);
        if (count(self::$fileLogList) > 20) {
            $batch_log = implode("\n", self::$fileLogList);
            file_put_contents($file,$log."\n",FILE_APPEND|LOCK_EX);
            self::$fileLogList = [];
        }

//        file_put_contents($file,$log."\n",FILE_APPEND|LOCK_EX);
    }

    /**
     * 将消息队列中丢失的消息当做失败处理
     * （需要跟踪问题）
     * @param $subject
     */
    public static function processNotInQueueMessage($subject)
    {
        //@todo: 状态为wait不在消息队列的，补偿进入队列
        $time = time();
        $where = [
            ['status', '=', DbMessage::MSG_STATUS_IN_QUEUE],
            ['create_time', '<', $time-3600*2],
        ];
        $item_list = DbMessage::select($subject, $where);
        foreach ($item_list as $item) {
            DbMessage::updateStatus($subject, $item['id'], DbMessage::MSG_STATUS_QUEUE_PROCESS_ERR);
            self::writeConsumeLog($subject, $item, false, '消息队列消息丢失');
        }
    }

}
