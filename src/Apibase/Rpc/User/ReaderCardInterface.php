<?php
/*
 * 读者证 服务方法接口
 *
 * ReaderCardInterface.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * 用于rpc服务类，rpc客户端调用方法类的接口定义
 */
namespace EasyUtils\Apibase\Rpc\User;

/**
 * 读者证 服务方法接口
 */
interface ReaderCardInterface
{

    /**
     * 读者证类型列表
     * @param int $aid   图书馆编号
     * @param int $circ_place_id 流通点  可选参数
     * @return array[] type_name,type_code,type_complete_code,card_deposit,max_borrow_num
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function listTypes($aid, $circ_place_id=0);

    /**
     * 开通读者证.
     *
     * @param int $aid   图书馆编号
     * @param string $rdid  读者证号码
     * @param string $rdname  读者姓名.
     * @param string $rdpasswd  读者证密码.
     * @param array $params  可选参数，具体含：
     *   [phone]: String 手机号.
    [unit]: String 单位.
    [certify]: String 身份证号.
    [sex]: Int 性别0男 1女，默认为0.
    [nation]: String 名族.
    [remark]: String 备注.
    [startdate]: String 卡启用日期，格式: 年-月-日.
    [borndate]: String 出生日期，格式: 年-月-日.
    [rdglobal]: Int 是否开通馆际读者. 0不开通，1开通。默认为0
     *
     * @return
     * [
     *      'rdid' => '701510',
     *      'rdpasswd' => '123456',
     *      'rdname' => 'test',
     *      'aid' => 3008,
     *      'readers_id' => '685'
     * ]
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function openCard($param);

    /**
     * 计算图书馆办卡剩余数量
     * @param int $aid
     * @param int $circ_place_id 可选参数
     * @return array both_media_num,ecard_media_num
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function checkLeftCardNum($aid, $circ_place_id = 0);
}

