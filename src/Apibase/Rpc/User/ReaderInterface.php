<?php
/*
 * 读者 服务方法接口
 *
 * ReaderInterface.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * 用于rpc服务类，rpc客户端调用方法类的接口定义
 */
namespace EasyUtils\Apibase\Rpc\User;


/**
 * 读者人脸 服务方法接口
 */
interface ReaderInterface
{
    /**
     * 根据aid和reader_id获取读者数据表中的信息
     *
     * @param number $aid aid
     * @param string $reader_id  读者id
     * @return array
     * @example
     * [
     *  'id' => 54,
     *   'reader_aid' => 3003,
     *  'reader_id' => '420111010010975',
     *  'name' => '林丽微',
     *  'password' => '123456',
     *  'college' => '博库开发测试',
     *  'profession' => '其它',
     *  'identity_title' => '成人证',
     *  'face_num' => 0,
     *  'create_time' => '2018-08-11 13:43:50',
     *  'update_time' => '2020-03-24 13:31:34',
     * ]
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function getByAidReaderid($aid, $reader_id);

    /**
     * 根据读者证号访问图书系统（读者系统or管理系统），获取读者基本信息
     * 注：对于图传该系统，因为是模拟管理系统登陆，可获取用户密码等信息，后去后同步更新到本地库中
     *
     * @return array   读者基本信息
     *                 图创系统另外含
     * @example
     * array (
     *   'rdtype' => '区馆通借通还成人读者',
     *  'rdglobal' => '1',
     *  'rdlib' => '1101',
     *  'rdtype_sn' => 'TJTX_DZ',
     *  'age' => 0,
     *  'totalloannum' => '45687',
     *  'rdenddate' => '2020-08-10',
     *  'rdstartdate' => '2018-08-11',
     *  'rdsex' => '0',
     *  'rdid' => '420111010010975',
     *  'rdpwd' => '123456',
     *  'rdunit' => '博库开发测试',
     *  'rdcertify' => '360729199210131820',
     *  'borrow_num' => '10',
     *  'rdphone' => '18779883571',
     *  'rdname' => '林丽微',
     *  'totalrenewnum' => '0',
     *  'loaned_num' => '0',
     *  'status' => '1',
     *  'major' => '其它',
     *  'show_rdtype' => '成人证',
     *  'show_unit' => '',
     *  'rdaid' => 3003,
     *  'readers_id' => 54
     * )
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function readerInfo($from_aid, $reader_id, $card_open_source=null);

    /**
     * 原cardList接口
     * 获取小程序用户已绑定的且在当前馆（入口图书馆）可用的所有证
     * @param int $uid 小程序的用户登录后的Uid
     * @param int $from_aid 入口图书馆的标识
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function listBindedReaders($uid, $from_aid);

    /**
     * 绑定读者证手机号
     * @param int $aid
     * @param int $readers_id
     * @param int $phone
     * @param int $uid
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function bindPhone($aid, $readers_id, $phone, $uid, $replace_binded=false);

    /**
     * 检测用户手机号是否已被绑定
     * @param int $aid
     * @param int $phone
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function checkPhoneBind($aid, $phone);

    /**
     * 解绑读者证绑定的手机号
     * @param int $aid
     * @param int $phone
     * @param string $reader_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function unbindPhone($aid, $phone, $reader_id='');

    /**
     * 根据uid获取用户最近绑定的读者证
     * @param int $uid
     * @param int $from_aid aid
     * @return array
     * @example return
     * [
     *   'uid' => 309,
     *   'readers_id' => '54',
     *   'from_aid' => 3003,
     *   'reader_id' => '420111010010975',
     * ]
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function getLatestReadersByUid($uid, $from_aid, $detail_info=false);

    /**
     * 电子读者证二维码登录,仅适用本馆读者在本馆设备上登录
     * @param int $aid
     * @param string $qcode 二维码的值
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function qcodeLogin($aid, $qcode);

    /**
     * 获取用户在某图书馆最近绑定的读者证信息
     *
     * @param int $uid 用户登录博库应用后的ID
     * @param int $from_aid 当前入口的图书馆标识
     * @return array
     * @example return
     * [
     *  'qcode' => '11303058520000000054',
     *   'readers_id' => 54,
     *   'logo' => 'http://image.bookgo.com.cn/wxLibrary2/uc/3003.png',
     *   'library_name' => '武汉市洪山区图书馆',
     *   'reader_aid' => 3003,
     *   'reader_id' => '420111010010975',
     *   'password' => '123456',
     *   'name' => '林丽微',
     *   'telephone' => '13419605886',
     *   'borrow_num' => 10,
     *   'show_rdtype' => '成人证',
     *   'show_unit' => '成人证',
     *   'face_num' => 1,
     * ]
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function getLatestReader4Login($uid, $from_aid);

    /**
     * 原getLatestReaderQcode接口
     * 获取用户在某图书馆最近绑定的读者证的博库二维码
     * @param int $uid  用户登录博库应用后的ID
     * @param int $from_aid  当前入口的图书馆标识
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function getLatestReaderWithQcode($uid, $from_aid, $get_detail=false);

    /**
     * 在手机上扫码登录
     * @param int $uid
     * @param string $reader_id
     * @param string $device_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function qlogin($uid, $reader_id, $device_id);

//    /**
//     * 修改馆用户积分
//     * @param int $readers_id
//     * @param string $point
//     * @return []
//     */
//    public function changePoint($readers_id, $point);

    /**
     * 删除读者信息
     * @param int $aid
     * @param string $reader_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function delete($aid, $reader_id);
}

