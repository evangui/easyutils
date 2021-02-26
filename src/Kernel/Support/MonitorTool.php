<?php
/*
 * 通用的接口请求监控工具帮助类
 *
 * MonitorTool.php
 * 2019-06-27 guiyj<guiyj007@gmail.com>
 */
namespace EasyUtils\Kernel\Support;

use think\console\Output;


class MonitorTool
{
    public static $errItems = [];   //失败项纪录列表
    public static $succItems = [];  //成功项纪录列表
    public static $succCnt = 0;

    /**
     * command output参数
     * @var \think\console\Output
     */
    public static $opt;

    /**
     * 监控脚本的标题
     */
    public static $monitorTitle;

    /**
     * 初始化传递command output参数，与监控脚本的标题
     * @param $output
     * @param $monitor_title
     */
    public static function init($monitor_title, $output='')
    {
        self::$opt = $output ?: new Output();
        self::$monitorTitle = $monitor_title;
    }

    public static function start($monitor_collections, $biz_id=0)
    {
        foreach ($monitor_collections as $collection) {
            if (!$collection['valid']) {
                continue;
            }
            if ($biz_id && $biz_id != $collection['biz_id']) {
                continue;
            }

            foreach ($collection['test_list'] as $lib_monitor_item) {
                MonitorTool::monitorSingleApi($collection['collection_name'], $lib_monitor_item);
            }

            //将错误消息进行邮件发送
            MonitorTool::sendMail($collection['collection_name'] ?? '');
            //将错误消息进行邮件发送
            self::reset();
        }
    }

    private static function reset()
    {
        self::$errItems = self::$succItems =[];
        self::$succCnt = 0;
    }

    /**
     * 监控通用配置规则的api
     * @param string $lib_name 图书馆名称
     * @param array $monitor_item 监控项目配置，示例如下
     * @example
     * [
     *   'url' => 'http://2016.bookgo.com.cn/api/hsslib/reader',
     *   'post_data' => [],
     *   'title' => '通用接口',
     *   'exclude_week_days' => '1',//根据周几来确定排除时间范围（如周一，周二闭馆，不检测，则设置exclude_week_days=1,2）
     *   'exclude_day_times' => '00:00-07:00,18:00-24:00',//如早上0点到7点，18:00到24点不开馆，不检测，则设置exclude_day_times=00:00-07:00,18:00-24:00
     *   'asserts' => [
     *      [
     *        'field' => 'data>current_day',    //监控返回参数字段，多级字段用>分隔
     *        'eq' => '100',                    //期望返回字段值等于指定值
     *        'gt' => '10',                     //期望返回字段值大于指定值
     *        'lt' => '5000',                   //期望返回字段值小于指定值
     *      ]
     *   ]
     * ]
     */
    public static function monitorSingleApi($lib_name, $monitor_item)
    {
        /**
         * 检测是否在指定的排除监控时间范围内
         */
        $now = time();
        $week_start  = strtotime(date('Y-m-d', strtotime('this week')));
        $today_start = strtotime(date('Y-m-d', $now));
        $today_str   = date('Y-m-d', $now);

        // 根据周几来确定排除时间范围（如周一，周二闭馆，不检测，则设置exclude_week_days=1,2）
        if (!empty($monitor_item['exclude_week_days'])) {
            $exclude_days = explode(',', $monitor_item['exclude_week_days']);

            foreach ($exclude_days as $day) {
                if (($now > ($week_start + ($day-1) * 86400)) && ($now < ($week_start + $day * 86400))) {
                    return;
                }
            }
        }

        // 根据一天的小时设置来确定排除时间范围
        //（如早上0点到7点，18:00到24点不开馆，不检测，则设置exclude_day_times=00:00-07:00,18:00-24:00）
        if (!empty($monitor_item['exclude_day_times'])) {
            $exclude_day_times = explode(',', $monitor_item['exclude_day_times']);
            foreach ($exclude_day_times as $start_end_time) {
                list($start_time_str, $end_time_str)   = explode('-', $start_end_time);
                $start_timestamp = strtotime("{$today_str} {$start_time_str}");
                $end_timestamp   = strtotime("{$today_str} {$end_time_str}");
                if (($now > $start_timestamp) && ($now < $end_timestamp)) {
                    return;
                }
            }
        }

        /**
         * 发送接口请求
         */
        echo ("task {$lib_name} {$monitor_item['title']}: start...\r\n");
        $time_start = microtime(true);
        $url = $monitor_item['url'];
        $res = http_curl($url, $monitor_item['post_data'], null, 6);
        $res_arr = json_decode($res, true);
        $use_time   = number_format(microtime(true) - $time_start, 4);

        /**
         * 比较结果项目的值
         */
        foreach ($monitor_item['asserts'] as $field_setting) {
            if (empty($field_setting)) {
                continue;
            }

            $err_msg = '';
            $field_key_list = explode('.', $field_setting['field']);
            $len = count($field_key_list);

            try {
                if ($len == 0) {
                    if (empty($res_arr)) {
                        $err_msg .= "|| 接口返回数据无效";
                    }
                    continue;
                } elseif ($len == 1) {
                    $verify_val = $res_arr[$field_key_list[0]];
                } elseif ($len == 2) {
                    $verify_val = $res_arr[$field_key_list[0]][$field_key_list[1]];
                } elseif ($len == 3) {
                    $verify_val = $res_arr[$field_key_list[0]][$field_key_list[1]][$field_key_list[2]];
                } elseif ($len == 4) {
                    $verify_val = $res_arr[$field_key_list[0]][$field_key_list[1]][$field_key_list[2]][$field_key_list[3]];
                }

                //进行设置校验
                if (isset($field_setting['eq']) && $verify_val != $field_setting['eq']) {
                    $err_msg .= "|| 接口返回数据不合法,({$field_setting['field']}) 期望等于 {$field_setting['eq']}，实际值为{$verify_val} ";
                }
                if (isset($field_setting['gt']) && $verify_val <= $field_setting['gt']) {
                    $err_msg .= "|| 接口返回数据不合法,({$field_setting['field']}) 期望大于 {$field_setting['gt']}，实际值为{$verify_val} ";
                }
                if (isset($field_setting['lt']) && $verify_val >= $field_setting['lt']) {
                    $err_msg .= "|| 接口返回数据不合法,({$field_setting['field']}) 期望小于 {$field_setting['gt']}，实际值为{$verify_val} ";
                }
            } catch (\Exception $e) {
                $err_msg .= $err_msg .= "|| 接口返回数据无效" . $e->getMessage();
            }

            if ($err_msg) {
                MonitorTool::setErrItem("{$monitor_item['title']}: {$err_msg}", $lib_name, $url, $monitor_item['post_data'], $res, $use_time);
            } else {
                MonitorTool::setSuccItem($url, $monitor_item['post_data'], $res, $use_time);
            }
            echo ("task {$lib_name} {$monitor_item['title']}: end, use time {$use_time}...\r\n");
            echo ("------------------------\r\n");
        }
    }

    /**
     * 控制台与邮件报警输出
     */
    public static function sendMail($sub_title='')
    {
        $err_arr = self::$errItems;
        $subject_title = self::$monitorTitle . ($sub_title ? ".{$sub_title}" : '');
        if (empty($err_arr)) {
            self::$opt->comment("\r\n{$subject_title}: Congratulation! All success! \r\n");
            return;
        }

        self::$opt->comment("\r\n{$subject_title}: Sorry! Something went wrong, errors below:\r\n");
        self::$opt->comment(var_export($err_arr, true));

        //错误分级报警
        $err_cnt          = count($err_arr);
        $monitor_item_cnt = $err_cnt + self::$succCnt;  //被监控项数量
        $num_tip          = "{$subject_title}: 监控接口总数：{$monitor_item_cnt}，错误数: {$err_cnt}";
        self::$opt->comment($num_tip);

        $level_warn_tip = '';
        $to_mails = null;
        //轻微问题，仅发给开发对应人员
        $to_dev_mails = [
            'guiyajun@bookgoal.com.cn',
        ];
        if ($err_cnt <= $monitor_item_cnt * 0.1) {
            $level_warn_tip = 'Info轻微问题，请优化';
            $to_mails = $to_dev_mails;
        } elseif ($err_cnt <= $monitor_item_cnt * 0.25) {
            $level_warn_tip = 'Warn轻微，可关注';
            $to_mails = $to_dev_mails;
        } elseif ($err_cnt <= $monitor_item_cnt * 0.5) {
            $level_warn_tip = 'Error重要，请解决';
        } else {
            $level_warn_tip = 'Critical严重，十万火急';
        }

        //监控环境文字提示
        $dev_mode = '个人环境';

        $subject = "{$dev_mode}({$subject_title})【{$level_warn_tip}】-接口监控报告({$err_cnt}/{$monitor_item_cnt})";
        $body    = '<pre>'.var_export($err_arr, true) .'</pre>';
        $body    = str_replace([
            "'msg'",
            "'lib'",
            "'func'",
            "'param'",
            "'res'",
            "'use_time'",
        ], [
            "'报警原因'",
            "'图书馆'",
            "'监控项'",
            "'请求参数'",
            "'返回数据'",
            "'用时(秒)'",
        ], $body);

        $body = "{$num_tip}。<hr>问题记录如下：</br>" . $body . '<pre>';
        if (empty($to_mails)) {
            //错误较多时，才发送成功条目信息
            $body .= "</br><hr></br> 成功记录项如下：</br> " . var_export(self::$succItems, true) .'</pre>';
        }

        $res = bg_alarm_by_mail($subject, $body, $to_mails); //the titile and body 标题和内容
        if ($res) {
            self::$opt->comment("\r\n{$subject_title}: Mail send success! \r\n");
        } else {
            self::$opt->comment("\r\n{$subject_title}: Mail send fail! \r\n");
        }
    }

    /**
     * 设置失败记录
     * @param $msg
     * @param $func
     * @param $param
     * @param $res
     * @param $use_time
     * @return array
     */
    public static function setErrItem($msg, $lib, $func, $param, $res, $use_time)
    {
        $item = compact('msg', 'lib', 'func', 'use_time', 'param', 'res');
        if ($msg) {
            self::$opt->error(".........Warning!.........");
            self::$opt->error("error occured: {$msg}, See more detail info ,check your mail: ");

            self::$errItems[] = $item;
        }
        return $item;
    }

    /**
     * 设置成功记录项
     * @param $func
     * @param $param
     * @param $res
     * @param $use_time
     */
    public static function setSuccItem($func, $param, $res, $use_time)
    {
        $item = compact('func', 'use_time', 'param', 'res');
        self::$succItems[] = $item;
        self::$succCnt++;
        return ;
    }

}