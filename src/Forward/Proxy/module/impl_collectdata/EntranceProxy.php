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

/**
 * 书籍模块代理的通用实现类
 */
class EntranceProxy extends CollectdataImplBase implements IEntranceProxy
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

    }

    public function bindFace($reader_id, $name, $photo_binary)
    {
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
    }
}