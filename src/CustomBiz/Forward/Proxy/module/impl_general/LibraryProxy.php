<?php
/*
 * 书籍模块代理的通用实现类
 *
 * BookProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书业务模块代理接口的通用实现
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general;

use EasyUtils\Kernel\Constant\ApiCodeConst;
use EasyUtils\Kernel\Support\HttpRequest;
use EasyUtils\Kernel\Traits\SingletonTrait;
use EasyUtils\CustomBiz\Forward\Service\ForwordProxyException;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IEntranceProxy;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\ILibraryProxy;

/**
 * 书籍模块代理的通用实现类
 */
class LibraryProxy extends GeneralImplBase implements ILibraryProxy
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
        $uri = 'entrance/reader/getReader';
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
            'photo_mode' => 'no',
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        // 原样返回接口成功数据
        return $res['data'];
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
//        $uri = "liblife/getdata/get_libseta/userid/{$reader_id}";
        $uri = "seat/seat/statCnt";
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        // 原样返回接口成功数据
        return $res['data'];
    }

    public function statSeatDuration($reader_id, $cacheOpt = ['_cacheTime'=> 86400])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
        }
//        $uri = "liblife/getdata/get_libseta/userid/{$reader_id}";
        $uri = "seat/seat/statDuration";
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        // 原样返回接口成功数据
        return $res['data'];
    }

    public function getUserFavSeat($reader_id, $cacheOpt = ['_cacheTime'=> 86400])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
        }
//        $uri = "liblife/getdata/get_libseta/userid/{$reader_id}";
        $uri = "seat/seat/getUserFavSeat";
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        // 原样返回接口成功数据
        return $res['data'];
    }
}