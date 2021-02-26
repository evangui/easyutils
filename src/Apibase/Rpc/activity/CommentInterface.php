<?php
/**
 * CommentInterface.php
 * 2020-04-08  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\Apibase\Rpc\activity;


interface CommentInterface
{
    /**
     * pagelist 评论列表
     *
     * @param string resource_type  资源类型
     * @param int resource_id   资源记录ID
     * @param int comment_type  留言类型：0留言，1评分
     * @param string sort_field 排序字段
     * @param string sort   排序值
     * @param int page  当前页码
     * @param int page_size 每页条数
     * @return array 查找到的资源相关评论信息 ，字段含：
     *      array datalist 评论的列表记录
     *      int cur_page 当前页码
     *      int page_size 每页条数
     *      int page_count 总页码数
     *      int total   评论总数
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function pagelist($params);

    /**
     * save 添加/保存评论
     *
     * @param int aid   馆方编号
     * @param int comment_pid  被回复评论ID
     * @param string content   评论的内容
     * @param string resource_type 资源类型
     * @param int resource_id  资源记录ID
     * @param int user_id  用户ID
     * @param int general_score 评分
     * @param int recommend 是否推荐
     * @param int comment_type 评论类型，0留言 1评价
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function save($params);

    /**
     * 获取资源评价的平均分
     * @param int $resource_id
     * @param string $resource_type
     * @return array
     */
    public function average($resource_id, $resource_type);

    /**
     * ulist 用户的评论列表
     *
     * @param int aid   馆方编号
     * @param int user_id   用户ID
     * @param string resource_type  资源类型
     * @param string resource_id    资源ID
     * @param int comment_type  留言类型：0留言，1评分
     * @param int page  当前页码
     * @param int page_size 每页条数
     * @return array 查找到的资源相关评论信息 ，字段含：
     *      array datalist 评论的列表记录
     *      int cur_page 当前页码
     *      int page_size 每页条数
     *      int page_count 总页码数
     *      int total   评论总数
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function ulist($params);
}