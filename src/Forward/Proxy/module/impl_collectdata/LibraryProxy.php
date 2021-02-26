<?php
/*
 * 书籍模块代理的通用实现类
 *
 * BookProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书业务模块代理接口的通用实现
 */
namespace EasyUtils\Forward\Service\Proxy\module\impl_collectdata;

use EasyUtils\Kernel\constant\ApiCodeConst;
use EasyUtils\Kernel\Support\HttpRequest;
use EasyUtils\Kernel\traits\SingletonTrait;
use EasyUtils\Forward\Service\ForwordProxyException;
use EasyUtils\Forward\Service\Proxy\module\IEntranceProxy;
use EasyUtils\Forward\Service\Proxy\module\ILibraryProxy;

/**
 * 书籍模块代理的通用实现类
 */
class LibraryProxy extends CollectdataImplBase implements ILibraryProxy
{
    use SingletonTrait;
    /**
     * 根据读者证获取门禁用户信息
     *
     * @param int $reader_id
     * @return array
     * @throws ForwordProxyException
     */
    public function getReader($reader_id)
    {
    }

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
    public function statSeatCnt($reader_id, $cacheOpt = ['_cacheTime'=> 86400])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
        }

    }

    public function statSeatDuration($reader_id, $cacheOpt = ['_cacheTime'=> 86400])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
        }

    }

    public function getUserFavSeat($reader_id, $cacheOpt = ['_cacheTime'=> 86400])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
        }

    }
}