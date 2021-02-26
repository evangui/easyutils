<?php
/*
 * 积分rpc核心方法服务 接口
 *
 * PointInterface.php
 * 2020-05-12 guiyj007@gmail.com
 *
 */
namespace EasyUtils\Apibase\Rpc\content;


/**
 * 
 */
interface ArticleInterface
{
    /**
     * 分页列表获取文章
     * @param $aid
     * @param $uid
     * @param array $condition
     * @param int $cur_page
     * @param int $page_size
     * @return array
     */
    public function pagelist(
        $aid,
        $board,
        $cat_id,
        $condition=[],
        $cur_page = 1,
        $page_size = 10,
        $order = ['id' => 'desc']
    );

    /**
     * 添加文章
     * @param $rule_type_id
     * @param $uid
     * @param $aid
     * @param string $memo
     * @return array
     */
    public function add($param);

    public function save($id, $data);

    /**
     * 详情
     * @param int $id 活动id
     * @param int $user_id 用户id
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function detail($id);

    /**
     * 增减字段值
     * @param $id
     * @param $field
     * @param $num
     * @return array
     */
    public function inc($id, $field, $num);

    /**
     * 删除文章
     * @return array
     */
    public function del($id);
}

