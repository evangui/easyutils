<?php
/*
 * 书籍模块代理的抽象类
 *
 * AbstractBookProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 用于定义所有图书业务模块的接口规格
 */
namespace EasyUtils\Forward\Service\Proxy\module;

use EasyUtils\Forward\Service\ForwordProxyException;

/**
 * @author guiyj007
 *    
 * 书籍模块代理的抽象类
 */
interface IEntranceProxy
{
    /**
     * 根据关键参数，查询门禁记录列表
     *
     * @param int $start_id
     * @param int $end_index
     * @param int $start_time
     * @param int $end_time
     * @return array
     * @throws ForwordProxyException
     */
    public function listData($start_index=0, $end_index=0, $start_time=0, $end_time=0);

    /**
     * 根据关键参数，查询门禁记录列表
     *
     * @param int $start_id
     * @param int $end_index
     * @param int $start_time
     * @param int $end_time
     * @return array
     * @throws ForwordProxyException
     */
    public function bindFace($reader_id, $name, $photo_binary);

    /**
     * 根据读者证获取门禁用户信息
     *
     * @param int $reader_id
     * @return array
     * @throws ForwordProxyException
     */
    public function getReader($reader_id);

    /**
     * 根据读者证获取门禁用户人脸信息
     *
     * @param int $reader_id
     * @return array
     * @throws ForwordProxyException
     */
    public function getReaderPhoto($reader_id);
    
}