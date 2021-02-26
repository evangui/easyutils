<?php
/**
 * ActiveUserInterface.php
 * 2020-04-07  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\Apibase\Rpc\activity;


interface ActiveUserInterface
{
    /**
     * mylist 用户活动列表
     *
     * @param int $aid  馆方编号
     * @param int $user_id  用户ID
     * @param int $user_status      活动用户的参与状态（0:报名;1:候补;5审核中）。默认为0
     * @param int active_type       活动类型（0:普通活动;1:志愿者活动）。默认0
     * @param int $active_status    活动状态 -1 全部 1 已结束 2 报名中 3报名已结束，活动未开始 4 进行中 5报名未开始，默认为4
     * @param string $sort_field    排序字段
     * @param string $sort  排序类型
     * @param int $page
     * @param int $page_size
     *
     * @return array 查找到的用户参与活动的相关信息 ，字段含：
     *      array datalist 活动的列表记录
     *      int page_count 总页数
     *      int total   总条数
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function mylist($params);

    /**
     * 获取用户时间范围内参与的活动
     *
     * @param int aid  馆方编号
     * @param int user_id  用户ID
     * @param int user_status      活动用户的参与状态（0:报名;1:候补;2:取消报名;3:取消候补;4:扫码报名;5:审核中;6:预约失败;-1:全部）。默认为0
     * @param int active_type      活动类型（0:普通活动;1:志愿者活动;-1:全部）。默认0
     * @param int start_time    起始时间。默认0
     * @param int end_time      截止时间。默认0
     *
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function calendarList($params);

    /**
     * countActive 某用户参与的活动数
     *
     * @param int $user_id
     * @param int $aid
     * @param int $active_type 活动类型（0:普通活动;1:志愿者活动）。默认0
     * @return array int 活动数
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function countActive($user_id, $aid, $active_type = 0);

    /**
     * signup 用户报名参加活动
     *
     * @param int active_id    活动ID
     * @param string signup_info   报名信息
     * @param int status   用户报名类型： 4 线下报名，5 预约
     * @param int user_id
     * @param string reader_id  读者证号
     * @param int active_round_id 活动场次ID
     * @param int reserve_number 预约人次
     * @param string companion_info 随行人员信息
     * @return array int
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function signup($params);

    /**
     * beCandidate 用户成为候补-想参加活动
     *
     * @param int active_id
     * @param int user_id
     * @param string reader_id
     * @param string signup_info
     * @param int active_round_id
     * @return array int
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function beCandidate($params);

    /**
     * cancelReserve 取消活动预约
     *
     * @param int $active_user_id
     * @return array int
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function cancelReserve($active_user_id);

    /**
     * cancelSignup 取消活动报名
     *
     * @param int $active_user_id
     * @return array int
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function cancelSignup($active_user_id);

    /**
     * cancelSignup 取消活动候补
     *
     * @param int $active_user_id
     * @return array int
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function cancelCandidate($active_user_id);

    /**
     * signin 活动签到
     *
     * @param int $active_id
     * @param int $user_id
     * @param int $active_round_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function signin($active_id, $user_id, $active_round_id = 0);

    /**
     * userWantJoin 用户想参加活动
     *
     * @param int $active_id
     * @param int $user_id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function userWantJoin($active_id, $user_id);

    /**
     * candidateRank 用户候补排名
     *
     * @param int $active_id  活动编号
     * @param int $user_id
     * @param int $active_round_id 活动场次ID
     * @return array int
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function candidateRank($active_id, $user_id, $active_round_id = 0);
}