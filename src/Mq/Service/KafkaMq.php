<?php
/*
 * kafka消息队列操作工具类
 *
 * KafkaMq.php
 * 2019-12-02 guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\Service;

use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\Mq\model\MessageBatchStat;

config('message.trace_log', 1);
/**
 *  kafka消息队列工具类
 */
class KafkaMq
{
    //producer实例句柄
    protected static $producers = null;
    protected static $consumers = null;
    protected static $consumerTopics = null;
    protected static $topicConf = null;

    static $fileLogList = [];

    /**
     * 单例获取$producer
     * @return \RdKafka\Producer
     */
    public static function getProducer($channel, $brokers=null)
    {
        $producer = null;
        if (!self::$producers[$channel]) {
            if (!$brokers) {
                $topic_config = config("message.{$channel}");
                if (empty($topic_config['brokers'])) {
                    $common_config = config("message.database");
                    $brokers = $common_config['kafka_brokers'];
                } else {
                    $brokers = $topic_config['brokers'];
                }
            }

            //针对低延迟进行了优化的配置。这允许PHP进程/请求尽快发送消息并快速终止
//          $global_conf->set('socket.timeout.ms', 50);
            $producer = new \RdKafka\Producer(self::getConf(false));
            $producer->addBrokers($brokers);
//            $producer->setLogLevel(LOG_DEBUG);
            self::$producers[$channel] = $producer;
        } else {
            $producer = self::$producers[$channel];
        }
        return $producer;
    }

    /**
     * @return \RdKafka\Conf
     */
    public static function getConf($consume=false)
    {
        $conf = new \RdKafka\Conf();
        if ($consume) {
//            $conf->set('enable.auto.commit', 'false');
            $conf->set('group.id', 'phpConsumerGroup');
        }
//        $conf->set('socket.timeout.ms', 50); // or socket.blocking.max.ms, depending on librdkafka version
        if (function_exists('pcntl_sigprocmask')) {
            pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
            $conf->set('internal.termination.signal', SIGIO);
        } else {
            $conf->set('queue.buffering.max.ms', 1);
        }
        return $conf;
    }

    /**
     * 单例获取$consumer
     * @return \RdKafka\Consumer
     */
    public static function getConsumer($channel, $brokers=null)
    {
        $consumer = null;
        if (!self::$consumers[$channel]) {
            if (!$brokers) {
                $topic_config  = config("message.{$channel}");
                if (empty($topic_config['brokers'])) {
                    $common_config = config("message.database");
                    $brokers = $common_config['kafka_brokers'];
                } else {
                    $brokers = $topic_config['brokers'];
                }
            }

            $consumer = new \RdKafka\Consumer(self::getConf(true));
            $consumer->addBrokers($brokers);
            self::$consumers[$channel] = $consumer;
        } else {
            $consumer = self::$consumers[$channel];
        }
        return $consumer;
    }

    /**
     * @return  \RdKafka\ConsumerTopic
     */
    public static function getConsumerTopic($channel, $brokers=null)
    {
        $producer = null;
        if (!self::$consumerTopics[$channel]) {
            $rk = self::getConsumer($channel, $brokers);
            $topic = $rk->newTopic($channel, self::getTopicConf());
            // Start consuming partition 0
            $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
            self::$consumerTopics[$channel] = $topic;
        }
        return self::$consumerTopics[$channel];
    }

    public static function getTopicConf()
    {
        if (!self::$topicConf) {
            $store_path = env('root_path') . 'data/kafka/';
            if (!is_dir($store_path)) {
                mkdir($store_path, 0755, 1);
            }
            $topic_conf = new \RdKafka\TopicConf();
            $topic_conf->set('auto.commit.interval.ms', 100);
            $topic_conf->set('offset.store.method', 'file');    //file, broker
            $topic_conf->set('offset.store.path', $store_path);  //root, www的路径不一样？
            $topic_conf->set('auto.offset.reset', 'smallest');
            self::$topicConf = $topic_conf;
        }
        return self::$topicConf;
    }

    /**
     * 生产者发送消息
     * $producer_callback [$this, 'func']
     */
    public static function produceMessage($channel, $message, $produce_callback=[])
    {
        $res = self::appendMessage($channel, $message, 0);
        return $res;
    }

    /**
     * redis保存主题数据
     * @param $aid
     * @param $datalist
     */
    public static function appendMessage($channel, $message, $err_times=0)
    {
        $rk = self::getProducer($channel);

        $cf = new \RdKafka\TopicConf();
        // -1必须等所有brokers同步完成的确认 1当前服务器确认 0不确认，这里如果是0回调里的offset无返回，如果是1和-1会返回offset
        // 我们可以利用该机制做消息生产的确认，不过还不是100%，因为有可能会中途kafka服务器挂掉
        $cf->set('request.required.acks', 0);
        $topic = $rk->newTopic($channel, $cf);
        $partition_id  =  0;

        $message = json_encode([$message, $err_times]);
        // 第一个参数：是分区。RD_KAFKA_PARTITION_UA代表未分配，并让librdkafka选择分区
        $res = $topic->produce(RD_KAFKA_PARTITION_UA, $partition_id, $message);
        $rk->flush(100);
        return $res;
    }

    /**
     * 保存主题数据
     * @param $aid
     * @param $datalist
     */
    public static function appendArrMessage($channel, $message, $err_times=0, $brokers='')
    {

        $rk = self::getProducer($channel, $brokers);
        $cf = new \RdKafka\TopicConf();
        // -1必须等所有brokers同步完成的确认 1当前服务器确认 0不确认，这里如果是0回调里的offset无返回，如果是1和-1会返回offset
        // 我们可以利用该机制做消息生产的确认，不过还不是100%，因为有可能会中途kafka服务器挂掉
//        $cf->set('request.required.acks', 1);
        $topic = $rk->newTopic($channel, $cf);
        $partition_id  =  0;

//        $message['__err_times'] = $err_times;
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        // 第一个参数：是分区。rd_kafka_partition_ua代表未分配，并让librdkafka选择分区
        $res = $topic->produce(RD_KAFKA_PARTITION_UA, $partition_id, $message);
        $rk->flush(100);
//        $rk->poll(100);
//        sleep(0.1);
        return $res;
    }

    /**
     *
     * @param string $channel
     * @param string $msg
     * @return bool
     */
    public static function mainConsume($channel='all'){
        $start_time = time();
        $conf = is_think5_1() ? config('message.') : config('message');
        $all_channel_list = [];
        foreach ($conf as $key => $val) {
            if (in_array($key, ['trace_log', 'database'])) {
                continue;
            }
            if (!isset($val['consume_mode']) || 'kafka' != $val['consume_mode']) {
                continue;
            }
            $all_channel_list[] = $key;
        }
        $channel_list = 'all' == $channel ? $all_channel_list : explode(',', $channel);
        if (empty($channel_list)) {
            return false;
        }

        foreach ($channel_list as $channel) {
            if (in_array($channel, ['trace_log', 'database'])) {
                continue;
            }
            if (!in_array($channel, $all_channel_list)) {
                echo "not assist channel: {$channel}";
                continue;
            }
            //一次任务只跑5分钟，定时任务注意设置
            while(time() - $start_time < 300) {
//            while(true) {
                self::consumeOneMessage($channel);
            }
        }
    }

    /**
     * 根据消息channel消费一条消息
     * @param $channel
     */
    public static function consumeOneMessage($channel, $brokers='', $callback_func='')
    {
        ve('start consumeOneMessage:', 0);
        $topic = self::getConsumerTopic($channel, $brokers);
        $message = $topic->consume(0, 120*1000);   // 第二个参数是等待收到消息的最长时间，1000是一秒

        ve($message, 0);
        config('message.trace_log') && trace($message, 'kafka');
        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                //没有错误打印信息
                try {
                    $res = self::invokeTask($channel, $message->payload, $callback_func, $brokers);
                    //消费完成后手动提交offset
//                    $res &&  self::getConsumer($channel)->commit($message);
                } catch (\Exception $e) {
//                      throw $e;   //for debug
                    trace("consumeOneMessage:[critical]: {$e->getCode()}-{$e->getMessage()}", 'kafka');
                    throw $e;
                }
                break;
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                echo "等待接收信息\n";
                sleep(rand(3,8));
                break;
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                echo "Timed out\n";
                break;
            default:
                biz_exception($message->errstr(), $message->err);
                break;
        }
    }


    /**
     * 由服务端唤起单个任务进行消费调用
     * @param $channel
     * @param $message
     * @return bool
     */
    public static function invokeTask($channel, $message, $callback_func='', $brokers='')
    {
        if ($callback_func) {
            return self::invokeTaskByHandler($channel, $message, $callback_func, $brokers);
        }

        $trace_log = config('message.trace_log');

        /**
         * 1 参数验证
         */
        $msg_arr = json_decode($message, true);
        $trace_log && trace('invokeTask:[info]:$msg_arr='. var_export($msg_arr, 1), 'kafka');
        if (!$msg_arr || count($msg_arr) != 2) {
            $trace_log && trace("invokeTask:[error]:invalid msg - {$message}", 'kafka');
            return false;
        }

        /**
         * 2 数据解析&消费回调方法解析
         */
        if (!isset($msg_arr[1])) {
            return false;
        }
        list($callback_param, $err_times) = $msg_arr;
        $config = config("message.{$channel}");
        if (!is_array($config)) {
            return false;
        }

        $trace_log && trace("invokeTask:[info]: {$channel} \$config=" . var_export($config, 1), 'kafka');
        if ( empty($config['job_class']) || empty($config['job_method'])) {
            //@todo: 处理未指定回调消费者异常
            $trace_log && trace('invokeTask:[error]: empty callback', 'mq');
            return false;
        }

        //错误次数超过次数，丢掉消息，回调消费者。记录问题，人为跟进
//        $max_err_times = config("message.{$channel}")['max_error_times'];
//        if ($err_times >= $max_err_times) {
//            if (!empty($config['job_max_error_method']) && method_exists($config['job_class'] , $config['job_max_error_method'])) {
//                $callback_func = $config['job_class'] . '::' . $config['job_max_error_method'];
//                $res = call_user_func_array($callback_func, [$callback_param]);
//            }
//            trace('invokeTask:[error]: consume_err_times超过规定次数，丢弃消息', 'mq');
//            return false;
//        }

        /**
         * 4 执行消费任务
         */
        $res = $err = '';
        try {
            $callback_func = $config['job_class'] . '::' . $config['job_method'];
            $trace_log && trace("invokeTask:[info]: {$callback_func},".var_export($callback_param,1), 'mq');
            $res = call_user_func_array($callback_func, [$callback_param]);
//            $res = rand(0,1);   //debug
        } catch (\Exception $e) {
//            throw $e; // for debug
            $err = $e->getMessage();
            trace("invokeTask:[callback error]: {$e->getCode()}|{$err}", 'mq');
        }
        trace("invokeTask:[callback res]: {$res}", 'mq');

        /**
         * 5 消费任务成功后。记录状态，记录日志
         */
        //response err&超时err: rePushQueue() 重新加入队列，及记录错误重试次数
//        (!$res || $err) && $err_res = self::processErrMessage($channel, $callback_param, $err_times+1);
        self::fileLog($channel, $res, $err);

        if (!$err) {
            return true;
        } else {
            return false;
        }
    }

    public static function invokeTaskByHandler($channel, $message, $callback_func, $brokers='')
    {
        $msg_arr = json_decode($message, true);
        //错误次数超过次数，丢掉消息，回调消费者。记录问题，人为跟进
       /* $max_err_times = 5;
        $err_times = empty($msg_arr['__err_times']) ? 0 : $msg_arr['__err_times'];
        if ($err_times >= $max_err_times) {
            return false;
        }*/

        /**
         * 4 执行消费任务
         */
        $res = $err = '';
        try {
            $res = call_user_func_array($callback_func, [$msg_arr]);
        } catch (\Exception $e) {
            $err = $e->getMessage();
            trace("invokeTask:[callback error]: {$e->getCode()}|{$err}", 'mq');
        }
        trace("invokeTask:[callback res]: {$res}", 'mq');

        /**
         * 5 消费任务成功后。记录状态，记录日志
         */
        //response err&超时err: rePushQueue() 重新加入队列，及记录错误重试次数
//        (!$res || $err) && $err_res = KafkaMq::appendArrMessage($channel, $msg_arr, $err_times+1, $brokers);
        if ($err) {
            return false;
        } else {
            return true;
        }

    }



    /**
     * response err&超时err: rePushQueue() 重新加入队列，及记录错误重试次数
     *
     * @param $channel
     * @param $message
     */
    public static function processErrMessage($channel, $callback_param, $err_times)
    {
        //再次加入到消息队列
//        return self::appendMessage($channel, $callback_param, $err_times);
    }

    protected static function fileLog($channel, $res, $err)
    {
        $dir = env('runtime_path') . "log/msg/";
        !file_exists($dir) && mkdir($dir, 0755, true);
        $file = $dir . "{$channel}" . date('Ymd') . ".log";
        $log = '[' . date('Y-m-d H:i:s') . '] ' . "-->" . ($res ? 'success' : 'fail');
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
     * 默认作业处理方法
     * 请注意不要用exit,die等中断进程方式
     * @param $param
     * @return bool //返回非空与true时，都代表消息队列任务 消费成功
     */
    public static function doJob($param)
    {
        echo ('job $param=' . var_export($param, 1));
        return true;
    }
}
