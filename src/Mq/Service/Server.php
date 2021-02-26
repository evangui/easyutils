<?php
/*
 * 消息队列服务端
 *
 * Server.php
 * 2019-04-19 10:50:00  guiyj<guiyj007@gmail.com>
 *
 * 队列服务端处理，主要处理
 * - 生产者生产消息入服务队列；调用消费者执行任务
 * - 生产与消费的辅助性统一调度
 */
namespace EasyUtils\Mq\Service;

use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\Mq\model\MessageBatchStat;

/**
 *  消息队列服务端
 */
class Server
{
    //每个主题频道的单个进程，单进程处理最多任务数
    protected static $singleThreadCapacity = 1;

    //每个主题频道，最多可开的进程数量
    protected static $threadMaxNum = 100;

    //每个主题频道，最少需要开的进程数量
    protected static $threadMaxMin = 1;

    //上一次DB存活的时间
    protected static $lastDbAliveTime = 0;

    //上一次补偿任务触发时间
    protected static $lastCompensateTime = 0;

    //redis实例句柄
    protected static $redis = null;

    //redis配置参数
    protected static $redisConf = ['index' => '6'];

    /**
     * redis 列表中的频道key
     * @param string $channel
     * @return string
     */
    public static function getChannelKey($channel)
    {
        return '' . $channel;
    }

    /**
     * 单例获取redis句柄
     * @return \Redis
     */
    public static function redis()
    {
        if (!self::$redis) {
            self::$redis = HandlerFactory::redis(self::$redisConf, 'message');
        }
        return self::$redis;
    }

    /**
     * 上一次DB存活的时间
     * @param int $time
     */
    public static function setLastDbAliveTime($time=0)
    {
        self::$lastDbAliveTime = $time ?: time();
    }

    /**
     * 上一次补偿任务的时间
     * @param int $time
     */
    public static function setLastCompensateTime($time=0)
    {
        self::$lastCompensateTime = $time ?: time();
    }

    /**
     * 添加消息到队列
     */
    public static function appendMessage($channel, $message_sn, $callback_func, $callback_param=[])
    {
        $redis   = self::redis();
        $message = [$message_sn, $callback_func, $callback_param];
//        v(call_user_func_array($callback_func, $callback_param));
        $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        //检测消息体长度，限制在2048字节
        if (strlen($message) > 4092) {
            biz_exception('消息体不能超过4092个字符');
        }

        //缓存消息，可在补偿任务里根据msg_sn捞出来重发
        self::cacheMessage($channel, $message_sn, $message);
//        $res = $redis->publish($channel, 1);    //触发频道订阅者消费

        //将入redis消息队列
        $res = $redis->lPush(self::getChannelKey($channel), $message);
        if (!$res) {
            //@todo: 失败时，增加预警提醒机制
            return $res;
        }

        self::removeMessageCache($channel, $message_sn);
        return $res;
    }

    /**
     * 添加消息到队列(用于补偿任务)
     */
    public static function appendMessageFromCache($channel, $message_sn)
    {
        $redis = self::redis();
        $message = self::cacheMessage($channel, $message_sn);
        if (empty($message_sn)) {
            //@todo: 记录问题日志
            return false;
        }
        $res = $redis->lPush(self::getChannelKey($channel), $message);
//        $res = $redis->publish(self::getChannelKey($channel), $message);
        if ($res) {
            self::removeMessageCache($channel, $message_sn);
        }
        //@todo: 失败时，增加预警提醒机制

        return $res;
    }

    /**
     * 消费出错时，重新将消息到队列
     */
    public static function reAppendMessage($channel, $message)
    {
        $redis   = self::redis();
        //加入redis消息队列
        $res = $redis->lPush(self::getChannelKey($channel), $message);
        return $res;
    }

    /**
     * 守护进程 多进程消费任务
     * 1. 开出n个进程
     * 2. 进程内自己抢资源处理
     *
     * @param \Redis $redis
     * @param string $channel
     * @param string $msg
     * @return bool
     */
    public static function mainConsume($channel, $thread_needed_num=0){
        config('database.break_reconnect', true);

        $redis = self::redis();
        $channel_key = self::getChannelKey($channel);

        //判断当前队列长度
//        $queue_len = $redis->lLen($channel_key);

        //根据队列长度，决定开多少个进程
        $thread_needed_num = $thread_needed_num ?: 1;

        $pids = array();
        for( $i = 0; $i < $thread_needed_num; $i++) {
                $pids[$i] = pcntl_fork();
            if ($pids[$i]) {
                //父进程:
                // @todo: 实时监控进程个数与队列任务个数。决定k进程 or 增加进程
                echo "No.{$i} child process wascreated, the pid is {$pids[$i]} \r\n";

            } elseif ($pids[$i] == 0) {
                //子进程
                $pid = posix_getpid();
                echo "process .{$pid} start \r\n";
                $count = 0;
                self::setLastDbAliveTime();
                self::setLastCompensateTime();
                do {
                    $count++;
                    if(0) { break; }    //设置子进程结束条件
                    self::consumeOneMessage($channel);
                    if ($count > 100) { //执行100个任务后，休眠5秒，给cpu踹息时间
                        sleep(5);
                        $count = 0;
                    }
//                    sleep(0.5);
                } while (true);

                echo "process .{$pid} end\r\n";
                posix_kill($pid, SIGTERM);
            }
        }
    }

    /**
     * 根据消息channel消费一条消息
     * @param $channel
     */
    public static function consumeOneMessage($channel){
        $redis       = self::redis();
        $channel_key = self::getChannelKey($channel);
//        v($redis->lRange($channel_key, 0, 100), 0);
        try {
            $message = $redis->rPop($channel_key);
        } catch (\RedisException $e) {
            $message = '';
        }
        //没有消息，等待3~8秒再试。并增加DB的断线重连机制（每隔5分钟，触发一次补偿任务）
        $now_time = time();
        if (empty($message) || ':0' == $message) {
            sleep(rand(3,8));

            //无消息消费时，30分钟后退出进程
            if ($now_time - self::$lastDbAliveTime > 30*60) {
                trace("consumeOneMessage:[info]: 20min idle. exit", 'mq');
                exit();
            }

            //30分钟内无消息消费时，5分钟触发一次补偿任务（顺便做断线重连机制）
            if ($now_time - self::$lastCompensateTime > 5*60) {
                Producer::compensate([$channel]);
                Consumer::processNotInQueueMessage($channel);
                self::setLastCompensateTime($now_time);
                trace("consumeOneMessage:[info]: compensate-setLastCompensateTime={$now_time}", 'mq');
            }
            return;
        }

        config('message.trace_log') && trace("consumeOneMessage:[info]: message={$message}", 'mq');
        try {
            config('message.trace_log') && trace("consumeOneMessage:[info]: {$channel}-{$message}", 'mq');
            Consumer::invokeTask($channel, $message);
        } catch (\Exception $e) {
//            throw $e;   //for debug
            trace("consumeOneMessage:[critical]: {$e->getCode()}-{$e->getMessage()}", 'mq');
//            v($e->getMessage());
            throw $e;
        }
    }

    /**
     * 缓存消息，以便补偿事务取消息内容
     * @param $channel
     * @param $message_sn
     * @param null $val
     * @return bool|string
     */
    private static function cacheMessage($channel, $message_sn, $val=null)
    {
        $redis = self::redis();

        $key = 'cache_' . $channel . $message_sn;
        if (null !== $val) {
            $redis->set($key, $val, 86400*3);
        } else {
            return $redis->get($key);
        }
    }

    private static function removeMessageCache($channel, $message_sn)
    {
        $key = 'cache_' . $channel . $message_sn;
        $redis = self::redis();
        $redis->del($key);
    }

    /**
     * 根据批次号统计当前任务消费数量
     * @param $channel
     * @param $batch_id
     * @return array
     */
    public static function statTaskByBatchIdInstantly($channel, $batch_id)
    {
        if (!$batch_id){
            biz_exception('batch_id不能为空');
        }
        $total        = DbMessage::count($channel, ['batch_id' => $batch_id]);
        $succ_cnt     = DbMessage::count($channel, ['batch_id' => $batch_id, 'status' => DbMessage::MSG_STATUS_QUEUE_PROCESS_OK]);
        $err_cnt      = DbMessage::count($channel, ['batch_id' => $batch_id, 'status' => DbMessage::MSG_STATUS_QUEUE_PROCESS_ERR]);
        $in_queue_cnt = DbMessage::count($channel, ['batch_id' => $batch_id, 'status' => DbMessage::MSG_STATUS_IN_QUEUE]);
        $processing_cnt = $total - $succ_cnt - $err_cnt - $in_queue_cnt;
        $first_item = DbMessage::find($channel, ['batch_id' => $batch_id]);

        //将统计信息写入数据库表
        return compact('total', 'succ_cnt', 'err_cnt', 'in_queue_cnt', 'processing_cnt', 'first_item');
    }

    /**
     * 根据批次号 统计当前任务消费数量（优先从DB统计表走）
     * @param $channel
     * @param $batch_id
     * @return array
     */
    public static function statTaskByBatchIdFromStatTable($channel, $batch_id)
    {
        if (!$batch_id){
            biz_exception('batch_id不能为空');
        }
        //从统计表获取数据
        $where = [
            'subject' => $channel,
            'batch_id' => $batch_id,
        ];
        $db_item = MessageBatchStat::where($where)->find();

        //存在记录
        $time = time();
        if (!empty($db_item['id'])) {
            //判断统计任务是否已完成
//            if ($db_item['finished'] && ($time - $db_item['create_time'] > 3600)) {
            if ($db_item['finished'] && ($time - $db_item['create_time'] > 86400*6)) {
                return $db_item;
            }
        }
        $stat_data = self::statTaskByBatchIdInstantly($channel, $batch_id);
        $data = [
            'subject'       => $channel,
            'batch_id'      => $batch_id,
            'total'         => $stat_data['total'],
            'succ_cnt'      => $stat_data['succ_cnt'],
            'err_cnt'       => $stat_data['err_cnt'],
            'in_queue_cnt'  => $stat_data['in_queue_cnt'],
            'update_time'   => $time,
        ];
        if ($data['total'] <= ($data['succ_cnt'] + $data['err_cnt']) ||
            ($time - $stat_data['first_item']['create_time'] > 86400*3)
        ) {
            $data['finished'] = 1;
        } else {
            $data['finished'] = 0;
        }

        if (!empty($db_item['id'])) {
            $res = MessageBatchStat::where(['id' => $db_item['id']])->update($data);
        } else {
            $data['create_time'] = $time;
            $res = MessageBatchStat::where(['id' => $db_item['id']])->insertGetId($data);
        }
        return $data;
    }
}
