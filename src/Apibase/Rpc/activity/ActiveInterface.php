<?php
/**
 * ActiveInterface.php
 * 2020-04-07  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\Apibase\Rpc\activity;


interface ActiveInterface
{
    /**
     * listCats 活动分类
     * @param int $aid
     * @return array[] id,name
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function listCats($aid);

    /**
     * pagelist 活动列表
     * @param int $aid 馆方编号
     * @param int $active_type 活动类型（0:普通活动;1:志愿者活动）。默认0
     * @param int $status 活动状态 -1 全部 1 已结束 2 进行中 3报名已结束，活动未开始 4 报名中 5报名未开始
     * @param string search_key 搜索关键词
     * @param int $cat1 一级分类id
     * @param int $cat2 二级分类id
     * @param string $sort_field 排序字段
     * @param string $sort 排序类型
     * @param int $page 页码
     * @param int $page_size 页记录大小
     * @param string longitude 经度
     * @param string latitude 纬度
     * @param int area_id 志愿者团队服务区域ID,默认0
     * @param int volunteer_team_id 志愿者团队ID,默认0
     *
     * @return['data'] array 查找到的相关信息 ，字段含：
     *      string  datalist
     *      string  cur_page
     *      string  page_size
     *      string  page_count
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function pagelist($params);

    /**
     * 获取活动场次列表
     * @param int $active_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function listRound($active_id);

    /**
     * detail 活动详情
     * @param int $id 活动id
     * @param int $user_id 用户id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function detail($id, $user_id = 0);

    /**
     * listUser 活动用户列表
     *
     * @param int $aid 馆方编号
     * @param int $user_id 用户uid，已登录用户传递该参数，可以获取该用户针对活动的报名情况概要说明
     * @param int $user_status  活动用户的参与状态（0:已报名;1:候补;2:取消报名 3:取消候补; 4:扫码报名）。默认为0
     * @param int $page  页码
     * @param int $page_size  页记录大小
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function listUser($params);

    /**
     * countUser 参加活动的用户数
     *
     * @param int $active_id 活动编号
     * @param int $aid 馆方编号
     *
     * @return array count_user,limit_num
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function countUser($active_id, $aid = 0);

    /**
     * count 馆方活动数
     *
     * @param int $aid
     * @param int $active_type 活动类型（0:普通活动;1:志愿者活动）。默认0
     * @return array int 活动数
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function count($aid, $active_type = 0);

    /**
     * getActiveField 通过活动id获取活动报名字段
     *
     * @param int $active_id 活动的ID
     * @return['data'] array 活动报名字段
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function getActiveField($active_id);
}