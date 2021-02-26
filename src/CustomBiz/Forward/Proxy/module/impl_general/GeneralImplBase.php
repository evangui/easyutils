<?php
/*
 * 通用的前置机代理对象实现用到的基本方法定义
 *
 * GeneralImplTrait.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 本文件定义的trait，用于给所有 通用的前置机模块代理对象，在实现时增加基本功能方法
 * 可作为基类用途使用
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general;

use EasyUtils\Apibase\ApiException;
use EasyUtils\Kernel\Constant\ApiCodeConst;
use EasyUtils\Kernel\Support\HttpRequest;
use EasyUtils\CustomBiz\Forward\Service\ForwordProxyException;
use EasyUtils\Kernel\Support\Str;
use think\facade\Log;

/**
 * @author guiyj007
 *        
 * 图书馆的前置机总代理对象抽象类
 */
abstract class GeneralImplBase
{
    protected $aid;

    /**
     * 默认错误重连次数
     */
    protected $errorRetryTimes = 1;

    /**
     * 调用的原始返回结果
     * @var mixed
     */
    protected $originalRes;

    /**
     * 获取调用的原始返回结果
     * @return string
     */
    public function getRes()
    {
        return $this->originalRes;
    }

    /**
     * 设置调用的原始返回结果
     * @param string $originalRes
     */
    public function setRes($originalRes)
    {
        $this->originalRes = $originalRes;
    }

    protected function setErrorRetryTimes($times) {
        $this->errorRetryTimes = $times;
        return $this;
    }

    public function initProxy($aid)
    {
        $this->aid = intval($aid);
        return $this;
    }
    
    /**
     * 获取默认通用代理方法的前置机接口请求地址
     * 
     * @param string $aid
     * @return string
     */
    protected function getGeneralApiUrl() 
    {
        $forword_proxy_conf = config('forword_proxy.' . $this->aid);
        return isset($forword_proxy_conf['api_url']) ? $forword_proxy_conf['api_url'] : '';
    }
    
    /**
     * 获取默认通用代理方法的前置机接口请求地址
     *
     * @param string $aid
     * @return string
     */
    public function getGeneralConf()
    {
        return config('forword_proxy.' . $this->aid);
    }

    /**
     * curl请求对外接口，带错误重试与异常识别
     *
     * @param string $uri         请求资源路径，如 uar/library/searchBook
     * @param array $param        接口请求参数
     * @param integer $try_times  接口遇到网络错误及约定错误码时，重试请求次数
     * @param integer $timeout    接口响应超时时间，单位：秒
     * @throws ApiException
     * @return array
     */
    protected function request($uri, $param=[], $timeout=6, $try_times=0)
    {
        $try_times = $try_times ?: $this->errorRetryTimes;
        $current_error_times = 0;
        $res_arr = [];
        while (!isset($res_arr['code']) && $current_error_times < $try_times) {
            $res_arr = $this->doRequest($uri, $param, $timeout);
            $current_error_times++;
        }

        // 异常状态处理
        if (!isset($res_arr['code'])) {
            throw new ApiException('网络请求失败', ApiCodeConst::NETWORK_ERR);
        }
        if ($res_arr['code'] != ApiCodeConst::BIZ_SUCCESS) {
            throw new ApiException($res_arr['msg'], ApiCodeConst::BIZ_ERR);
        }
        return $res_arr;
    }

    /**
     * 请求图书馆前置机对外接口
     *
     * @param string $uri 请求资源路径，如 uar/library/searchBook
     * @param array $param 接口请求参数
     * @return array
     * @throws ForwordProxyException
     */
    protected function doRequest($uri, $param, $timeout=6)
    {
        // 组装接口请求地址
        $url = $this->getGeneralApiUrl() . $uri;
        $general_conf = $this->getGeneralConf();
        if (empty($general_conf)) {
            throw new ForwordProxyException('配置未找到');
        }
        $proxy_param = isset($general_conf['proxy']) ? $general_conf['proxy'] : '';

        // 请求接口
        $time_start = microtime(true);
        $res        = HttpRequest::send($url, $param, null, $timeout, $proxy_param);
        $this->setRes($res);
        $res        = Str::clearBom($res);
        $res_arr    = json_decode($res,true);

        $used_time  = number_format(microtime(true) - $time_start, 4);
        // 写日志
        if (defined('THINK_VERSION')) {
            if (config('selflog_debug')) {
                \think\Log::write("【req_ForwordProxyGeneral】输入数据>>>>>>>{$url}：" .print_r($param,true));
                \think\Log::write("【req_ForwordProxyGeneral】{$used_time}s 输出数据<<<<<<<{$url}：". $res);
                \think\Log::write("【req_ForwordProxyGeneral】 格式化输出<<<<<<<{$url}：". var_export($res_arr, true));
            }
        } else {
            if (env('selflog_debug')) {
                \think\facade\Log::write("【req_ForwordProxyGeneral】输入数据>>>>>>>{$url}：" .print_r($param,true));
                \think\facade\Log::write("【req_ForwordProxyGeneral】{$used_time}s 输出数据<<<<<<<{$url}：". $res);
                \think\facade\Log::write("【req_ForwordProxyGeneral】格式化输出<<<<<<<{$url}：". var_export($res_arr, true));
            }
        }
        return $res_arr;
    }

    /**
     * 请求图书馆前置机对外接口
     *
     * @param string $uri 请求资源路径，如 uar/library/searchBook
     * @param array $param 接口请求参数
     * @return array
     * @throws ForwordProxyException
     */
    protected function sendStream($uri, $file, $is_binary=false)
    {
        // 组装接口请求地址
        $url = $this->getGeneralApiUrl() . $uri;
        $general_conf = $this->getGeneralConf();
        if (empty($general_conf)) {
            throw new ForwordProxyException('配置未找到');
        }
        // 请求接口
        $time_start = microtime(true);
        $res        = HttpRequest::sendStreamFile($url, $file, $is_binary);
        $this->setRes($res);
        $res        = Str::clearBom($res);
        $res_arr    = json_decode($res,true);
        $used_time  = number_format(microtime(true) - $time_start, 4);

        // 写日志
        if (defined('THINK_VERSION')) {
            if (config('selflog_debug')) {
                \think\Log::write("【req_ForwordProxyGeneral】输入数据>>>>>>>{$url}：");
                \think\Log::write("【req_ForwordProxyGeneral】{$used_time}s 输出数据<<<<<<<{$url}：\r\n{$res}\r\n" . var_export($res_arr, true));
            }
        } else {
            if (env('selflog_debug')) {
                \think\facade\Log::write("【req_ForwordProxyGeneral】输入数据>>>>>>>{$url}：");
                \think\facade\Log::write("【req_ForwordProxyGeneral】{$used_time}s 输出数据<<<<<<<{$url}：\r\n{$res}\r\n" . var_export($res_arr, true));
            }
        }

        // 异常状态处理
        if (!isset($res_arr['code'])) {
            throw new ForwordProxyException('网络请求失败', ApiCodeConst::NETWORK_ERR);
        }
        if ($res_arr['code'] != ApiCodeConst::BIZ_SUCCESS) {
            throw new ForwordProxyException($res_arr['msg'], ApiCodeConst::BIZ_ERR);
        }
        return $res_arr;
    }
}
