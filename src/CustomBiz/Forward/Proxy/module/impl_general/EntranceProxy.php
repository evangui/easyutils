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

/**
 * 书籍模块代理的通用实现类
 */
class EntranceProxy extends GeneralImplBase implements IEntranceProxy
{
    use SingletonTrait;

    /**
     * 根据关键参数，查询门禁记录列表
     * 注：这里暂并不定义为抽象方法。以减轻当增加新家族的产品的工作量
     *
     * @param int $start_id
     * @param int $step
     * @param int $start_time
     * @param int $end_time
     * @return array
     * @throws ForwordProxyException
     */
    public function listData($start_index=0, $end_index=0, $start_time=0, $end_time=0)
    {
        $params = [
            'aid'         => $this->aid,
            'start_index' => $start_index,
            'end_index'   => $end_index,
            'start_time'  => $start_time,
            'end_time'    => $end_time,
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request('entrance/entrance/listData', $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        // 原样返回接口成功数据
        return $res;
    }

    public function bindFace($reader_id, $name, $photo_binary)
    {
        $uri = 'entrance/reader/bindFace';
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
            'name'      => $name,
            'photo'     => $photo_binary,
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        // 原样返回接口成功数据
        return ApiCodeConst::BIZ_SUCCESS == $res['code'] ? true : false;
    }

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
     * 根据读者证获取门禁用户信息
     *
     * @param int $reader_id
     * @return array
     * @throws ForwordProxyException
     */
    public function getReaderPhoto($reader_id)
    {
        $uri = 'entrance/reader/getReader';
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
            'photo_mode' => 'only',
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }
        // 原样返回接口成功数据
        return isset($res['data']['photo_base64']) ? $res['data']['photo_base64'] : '';
    }

    /**
     * 根据起止时间，统计查询该时间范围的进出人数
     *
     * @param int $reader_id
     * @return array
     * @throws ForwordProxyException
     */
    public function statInOut($start_time, $end_time)
    {
        $uri = 'entrance/entrance/statInOut';
        $params = [
            'aid'        => $this->aid,
            'start_time' => $start_time,
            'end_time'  => $end_time,
        ];
        try {
            $res = $this->setErrorRetryTimes(2)->request($uri, $params);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        /*{
            "code": 0,
            "msg": "",
            "data": {
                "in_cnt": 62,
                "out_cnt": 93
            }
        }*/
        // 原样返回接口成功数据
        return $res['data'];
    }

    /**
     * 根据关键参数，查询门禁记录列表
     * 注：这里暂并不定义为抽象方法。以减轻当增加新家族的产品的工作量
     *
     * @param int $start_id
     * @param int $step
     * @param int $start_time
     * @param int $end_time
     * @return array
     * @throws ForwordProxyException
     */
    public function uploadImg($file, $is_binary=false)
    {
        $uri = 'entrance/uploader/receiveImg?aid=' . $this->aid;
        try {
            $res = $this->sendStream($uri, $file, $is_binary);
        } catch (\Exception $e) {
            throw new ForwordProxyException($e->getMessage(), $e->getCode());
        }

        return $res['data'];
    }
}