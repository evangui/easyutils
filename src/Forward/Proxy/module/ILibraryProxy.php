<?php
/*
 * 图书借阅模块代理的抽象类
 *
 * AbstractBrProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 用于定义所有图书借阅业务模块的接口规格
 */
namespace EasyUtils\Forward\Service\Proxy\module;

use EasyUtils\Forward\Service\ForwordProxyException;

/**
 * 图书借阅模块代理的抽象类
 */
interface ILibraryProxy
{
    /**
     * 统计读者占座数据
     * @param $reader_id
     * @param array $cacheOpt
     * @return false|mixed|string
     * @throws ForwordProxyException
     * @example
     * [
     *    'max' => 183,
     *    'avg' => 80,
     *    'mycount' => 60,
     *    'rank_num' => 30,
     *    'my_bar' => 80,
     *    'avg_bar' => 60,
     * ]
     */
    public function statSeatCnt($reader_id, $cacheOpt = ['_cacheTime'=> 86400]);

    /**
     * @param $reader_id
     * @param array $cacheOpt
     * @return mixed
     * {"code":0,"msg":"","data":{"max":2948876,"avg":819132.09,"my":"12438","my_bar":1,"avg_bar":28,"max_duration_day":{"duration":"2905","day":"20180917"}}}
     */
    public function statSeatDuration($reader_id, $cacheOpt = ['_cacheTime'=> 86400]);

    /**
     * @param $reader_id
     * @param array $cacheOpt
     * @return mixed
     * @example {"code":0,"msg":"","data":{"seat":"朗读区 057号","cnt":159}}
     */
    public function getUserFavSeat($reader_id, $cacheOpt = ['_cacheTime'=> 86400]);

}