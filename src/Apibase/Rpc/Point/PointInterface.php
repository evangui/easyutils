<?php
/*
 * 积分rpc核心方法服务 接口
 *
 * PointInterface.php
 * 2020-05-12 guiyj007@gmail.com
 *
 */
namespace EasyUtils\Apibase\Rpc\Point;


/**
 * 
 */
interface PointInterface
{
    /**
     * 分页列表获取用户积分日志
     * @param $aid
     * @param $uid
     * @param array $condition
     * @param int $cur_page
     * @param int $page_size
     * @return array
     */
    public function pagelistLog(
        $aid,
        $uid,
        $condition=[],
        $cur_page = 1,
        $page_size = 10
    );

    /**
     * 修改馆用户积分
     * @param $rule_type_id
     * @param $uid
     * @param $aid
     * @param $readers_id
     * @param string $memo
     * @return array
     */
    public function changeLibUserPoint($rule_type_id, $uid, $aid, $memo='');

    /**
     * 获取馆积分规则类型列表
     * @param $aid
     * @return array
     */
    public function listPointRule($aid, $group_plus_minus=true, $auto_init_when_not_exist=false);
}

