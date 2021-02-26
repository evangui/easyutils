<?php
/*
 * 图书借阅模块代理的通用实现类
 *
 * BrProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书借阅业务模块的代理接口的通用实现
 */
namespace EasyUtils\Forward\Service\Proxy\module\impl_collectdata;

use EasyUtils\Apibase\ApiException;
use EasyUtils\Kernel\constant\ApiCodeConst;
use EasyUtils\Kernel\traits\SingletonTrait;

/**
 * 图书借阅模块代理的通用实现类
 */
class ToolProxy extends CollectdataImplBase
{
    use SingletonTrait;

    /**
     * 前置机对成蹊api的请求中转调用
     * lthistory.getAllBorrow_pageData
     * @return number[]  图书馆借阅量综合统计信息。目前仅含max:最大借阅量 ,avg:平均借阅量
     * @throws \EasyUtils\Forward\Service\ForwordProxyException
     * @throws \Exception
     */
    public function chengxiRelay($uri, $data, $try_times=0)
    {
        $try_times = $try_times ?: $this->errorRetryTimes;
        $params = [
            'aid' => $this->aid,
            'uri'=> $uri
        ];
        $params = array_merge($params, $data);

        $current_error_times = 0;
        $res_arr = [];
        while (!isset($res_arr['errorCode']) && $current_error_times < $try_times) {
            $res_arr = $this->doRequest('libsys/chengxi_relay/forward', $params, 10);
            $current_error_times++;
        }

        // 异常状态处理
        if (!isset($res_arr['errorCode'])) {
            throw new ApiException('网络请求失败', ApiCodeConst::NETWORK_ERR);
        }
        if ($res_arr['errorCode'] != ApiCodeConst::BIZ_SUCCESS) {
            throw new ApiException($res_arr['errorMsg'], ApiCodeConst::BIZ_ERR);
        }

        return $res_arr['data'];
    }


}