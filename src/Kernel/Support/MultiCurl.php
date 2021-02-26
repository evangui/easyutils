<?php
namespace EasyUtils\Kernel\Support;

class MultiCurl
{
    protected static $process_num_total = 0;
    protected static $process_num_finished = 0;

    /**
     * 从被请求的多个应用信息，来判断哪些应用是最近更新的应用
     * 通过curl进行多进程并发请求
     *
     * @param unknown $apps
     *            - 应用信息数组，每个应用必须包含键名：appid, release_date
     * @param number $delay
     * @param number $timeout
     * @return multitype:multitype:unknown
     * @author: guiyj007
     * @example
        //压测某个页面并发1000访问
        $allUrls = array();
        for ($x=0; $x<100; $x++) {
            $allUrls[] = 'http://www.hangowa.com/';
        }
        $multApiUrls = array();
        $n = 100; //调用服务端接口的并发请求个数
        foreach ($allUrls as $_url) {
            $multApiUrls[] = $_url;
            if (count($multApiUrls) >= $n) {
                // 根据进程数量，批量远程获取内容
                $rs = HttpRequest::multiGet($multApiUrls);
                $multApiUrls = array();
                sleep(2);
            }
        }
     */
    public static function send($callback, $urls, $header = null, $timeout=8, $proxy='')
    {
        $queue = curl_multi_init();
        $map = array();
        self::$process_num_total = count($urls);

        foreach ($urls as $url) {
            $ch = curl_init ();
            curl_setopt ( $ch, CURLOPT_URL, $url );
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip"); // gzip
            ! empty($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            if (strpos ( $url, 'https://' ) !== false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 这个是重点。
                curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
            }
            if($proxy) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
            }
            if (! empty ( $data )) {
                curl_setopt ( $ch, CURLOPT_POST, 1 );
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_multi_add_handle($queue, $ch);
            $map[(string) $ch] = $url;
        }
        $responses = array();
        do {
            while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM);
            if ($code != CURLM_OK) {
                break;
            }
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($queue)) {
                self::$process_num_finished++;

                // get the info and content returned on the request
                $info = curl_getinfo($done['handle']);
                $error = curl_error($done['handle']);
                $results = '';
                if (200 == $info['http_code']) {
                    $data = curl_multi_getcontent($done['handle']);
                    $results = self::multiCallback($callback, $data, $info);
                }
                // $responses[$map[(string)$done['handle']]] = compact('info', 'error');
                $responses[] = $results;
                // remove the curl handle that just completed
                curl_multi_remove_handle($queue, $done['handle']);
                curl_close($done['handle']);
            }
            // Block for data in / output; error handling is done by curl_multi_exec
            if ($active > 0) {
                curl_multi_select($queue, 0.5);
            }
        } while ($active);
        curl_multi_close($queue);
        return $responses;
    }
    /**
     * 对每个url请求，进行回调处理
     * 处理动作为：
     *   比较itunes应用更新时间，与数据库中应用的发布时间，
     *   如有可更新应用，则写入可更新应用列表中
     *
     * @param unknown $data
     * @param number $delay
     * @return multitype:Ambigous <multitype:unknown >
     */
    private static function multiCallback($callback, $data, $process_info) {
        //单进程回调处理逻辑（针对服务端接口放回信息的处理）
        // 写日志
        if (defined('THINK_VERSION')) {
            \think\Log::write("【multicurl multiCallback】执行任务信息>>>>>>>：" .var_export($process_info,true));
        } else {
            \think\facade\Log::write("【multicurl multiCallback】执行任务信息>>>>>>>：" .var_export($process_info,true));
        }

        $callback && call_user_func_array($callback, $data, $process_info['url']);
    }

    public static function checkProcessAllFinished()
    {
        return self::$process_num_finished == self::$process_num_total;
    }

}
