<?php
/*
 * 读者人脸 服务方法接口
 *
 * ReaderFaceInterface.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * 用于rpc服务类，rpc客户端调用方法类的接口定义
 */
namespace EasyUtils\Apibase\Rpc\User;


/**
 * 读者人脸 服务方法接口
 */
interface ReaderFaceInterface
{
    /**
     * 绑定新上传的人脸到某读者证
     *
     * 验证绑定人脸是否已经绑在该用户下(如已绑定，提示已绑定该人脸，退出)
     * 新增绑定人脸到百度人脸库
     * 更新用户已绑定人脸数
     * @param int $aid
     * @param string $reader_id
     * @param string $photo_base64
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bind($aid, $reader_id, $photo_base64='');
    /**
     * 解除某读者证下的所有人脸信息
     * @param int $aid
     * @param string $reader_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function unbind($aid, $reader_id);

    /**
     * after3rdBind 人脸绑定后置业务处理
     * @method GET, POST
     *
     * 在第三方人脸库平台（如百度），做绑定与解绑后 的博库业务处理
     * 更新博库服务端记录的用户绑脸信息
     * 2019-06-20  guiyj<guiyj007@gmail.com>
     *
     * @param  int  $aid
     * @param  string  $reader_id
     * @param  int  $op_type 业务操作类型（1：绑定人脸，2：解绑读者证下所有人脸）
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function after3rdBind($aid, $reader_id, $op_type);
    /**
     * 获取用户百度已绑定信息，同步更新到db（目前仅同步人脸数）
     * 用于在获取读者证信息接口里，如果绑定的人脸数字段为null，则触发
     * @param int $aid
     * @param string $reader_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function syncBindedInfo($aid, $reader_id);
    /**
     * 解除某读者证下的所有人脸信息，并将待绑定人脸与身份证相关联
     * @param int $aid
     * @param string $reader_id
     * @param string $id_card
     * @param string $photo_base64
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function unbindForPreReaderCard($aid, $reader_id, $id_card, $photo_base64);

    /**
     * 检测人脸是否已绑定 并做人脸质量检测
     * @param int $aid
     * @param string $id_card
     * @param string $photo_base64
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function detectForPreReaderCard($aid, $id_card, $photo_base64);
    /**
     * 人脸检测与属性分析
     * 检测图片中的人脸并标记出位置信息;
     * @param string $photo_base64
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function detect($photo_base64);

    /**
     * 拉取人脸数据
     * @param int $aid
     * @param int $sync_type
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function pullSyncFace($aid, $sync_type);

    /**
     * 拉取人脸数据同步的结果通知回调接口
     * @param int $aid
     * @param
     * @param int $sync_type
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function syncFaceCallback($aid, $sync_items, $sync_type);
}

