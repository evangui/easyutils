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
 *  简单模式消息队列工具类
 */
class SimpleMq
{
    /**
     * redis实例句柄
     * @var \Redis
     */
    protected static $redis = null;

    //redis配置参数
    protected static $redisConf = ['index' => '6'];

    static $fileLogList = [];

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
     * 主动关闭redis连接（多进程时，父进程需要主动关闭redis连接）
     * @return \Redis
     */
    public static function closeRedis()
    {
        if (self::$redis) {
            self::$redis.close();
        }
    }

    /**
     * 生产者发送消息
     * $producer_callback [$this, 'func']
     */
    public static function produceMessage($channel, $callback_param=[])
    {
        return self::appendMessage($channel, $callback_param, 0);
    }

    /**
     * redis保存主题数据
     * @param $channel
     * @param array $callback_param
     * @param int $err_times    当前消息错误次数
     */
    public static function appendMessage($channel, $callback_param=[], $err_times=0)
    {
        $redis = self::redis();
        $message = [$callback_param, $err_times];
//        v(call_user_func_array($callback_func, $callback_param));
        $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        $res = $redis->lPush($channel, $message);
        return $res;
    }

    public static function asyncCall($channeL_method, $args, $ttl=3, $data_token='')
    {
        if (!$data_token) {
            $channel = 'asyncCall-' . $channeL_method;
            $message = [
                'args' => $args,
                'data_token' => 'k' . do_order_sn()
            ];

            trace($channel . var_export($message, true));
            //发送消息到消息队列
            $produce_res = SimpleMq::produceMessage($channel, json_encode($message));
            if (false === $produce_res) {
                throw new \Exception('produceMessage error');
            }
        }

        $time = microtime(true);
        $redis = self::redis();
        while (time() - $time < $ttl) {
            $res = $redis->get($message['data_token']);
            if ($res) {
                return $res;
            }
            sleep(0.1);
        }
        return false;
    }

    /**
     * 异步调用方法的消费者数据存储。以便asyncCall方法轮询获取数据
     * @param $data_token
     * @param $data
     * @return bool
     */
    public static function asyncReturn($data_token, $data)
    {
        !is_string($data) && $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        return  self::redis()->setex($data_token, 60*2, $data);
    }

    //异步消费者执行示例
    public static function asyncExec($params)
    {
        $params = json_decode($params, true);
        $data = $params['args'];
        $data_token = $params['data_token'];
        $res = json_encode($data_token) . $data_token ;
        return self::asyncReturn($data_token, $res);
    }

    /**
     *
     * @param \Redis $redis
     * @param string $channel
     * @param string $msg
     * @return bool
     */
    public static function mainConsume($channel='all'){
        config('database.break_reconnect', true);

        $start_time = time();
        $conf = is_think5_1() ? config('message.') : config('message');
        $all_channel_list = [];
        foreach ($conf as $key => $val) {
            if (in_array($key, ['trace_log', 'database'])) {
                continue;
            }
            if (!isset($val['consume_mode']) || 'simple' != $val['consume_mode']) {
                continue;
            }
            $all_channel_list[] = $key;
        }


        $channel_list = 'all' == $channel ? $all_channel_list : explode(',', $channel);
        foreach ($channel_list as $channel) {
            if (in_array($channel, ['trace_log', 'database'])) {
                continue;
            }
            if (!in_array($channel, $all_channel_list)) {
                echo "not assist channel: {$channel}";
                continue;
            }
            //一次任务只跑5分钟，定时任务注意设置
            $count = 0;
            while(time() - $start_time < 300) {
                $count++;
                self::consumeOneMessage($channel);

                if ($count > 100) { //执行100个任务后，休眠5秒，给cpu踹息时间
                    sleep(3);
                    $count = 0;
                }
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
//            sleep(rand(3,8));
            sleep(0.3);
            return;
        }

        $trace_log = config('message.trace_log');
        $trace_log && trace("consumeOneMessage:[info]: message={$message}", 'mq');
        try {
            config('message.trace_log') && trace("consumeOneMessage:[info]: {$channel}-{$message}", 'mq');
            self::invokeTask($channel, $message);
        } catch (\Exception $e) {
//            throw $e;   //for debug
            trace("consumeOneMessage:[critical]: {$e->getCode()}-{$e->getMessage()}", 'mq');
//            v($e->getMessage());
            throw $e;
        }
    }

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
        if (!$msg_arr || count($msg_arr) < 2) {
            $trace_log && trace("invokeTask:[error]:invalid msg - {$message}", 'mq');
            return false;
        }

        /**
         * 2 数据解析&消费回调方法解析
         */
        list($callback_param, $err_times) = $msg_arr;
        $config = config("message.{$channel}");
        if (!is_array($config)) {
            return false;
        }

        $trace_log && trace("invokeTask:[info]: {$channel} \$config=" . var_export($config, 1), 'mq');
        if (empty($config['callback']) && empty($config['job_class'])) {
            //@todo: 处理未指定回调消费者异常
            $trace_log && trace('invokeTask:[error]: empty callback', 'mq');
            return false;
        }

        //错误次数超过次数，丢掉消息，回调消费者。记录问题，人为跟进
        $max_err_times = config("message.{$channel}")['max_error_times'];
        if ($err_times >= $max_err_times) {
            if (!empty($config['job_max_error_method']) && method_exists($config['job_class'] , $config['job_max_error_method'])) {
                $callback_func = $config['job_class'] . '::' . $config['job_max_error_method'];
                $res = call_user_func_array($callback_func, [$callback_param]);
            }
            trace('invokeTask:[error]: consume_err_times超过规定次数，丢弃消息', 'mq');
            return false;
        }

        if (!empty($config['callback'])) {
            $config['job_class']  = $config['callback'][0];
            $config['job_method'] = $config['callback'][1];
        }
        $callback_func = $config['job_class'] . '::' . $config['job_method'];

        /**
         * 4 执行消费任务
         */
        $res = $err = '';
        try {
            $trace_log && trace("invokeTask:[info]: {$callback_func},".var_export($callback_param,1), 'mq');
            $res = call_user_func_array($callback_func, [$callback_param]);
//            $res = rand(0,1);   //debug
        } catch (\Exception $e) {
//            throw $e; // for debug
            $err = $e->getMessage();
            trace("invokeTask:[callback error]: {$e->getCode()}|{$err}", 'mq');
        }

        /**
         * 5 消费任务成功后。记录状态，记录日志
         */
        //response err&超时err: rePushQueue() 重新加入队列，及记录错误重试次数
        (!$res || $err) && $err_res = self::processErrMessage($channel, $callback_param, $err_times+1);
        self::fileLog($channel, $res, $err);

        if ($res) {
            return true;
        } else {
            return false;
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
        return self::appendMessage($channel, $callback_param, $err_times);
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

}
