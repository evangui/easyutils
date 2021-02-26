<?php
/*
 * 图书借阅模块代理的通用实现类
 *
 * BrProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书借阅业务模块的代理接口的通用实现
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_collectdata;

use EasyUtils\Kernel\traits\SingletonTrait;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IBrProxy;

/**
 * 图书借阅模块代理的通用实现类
 */
class BrProxy extends CollectdataImplBase implements IBrProxy
{
    use SingletonTrait;

    /**
     * 获取指定图书馆的平均借阅量和最高借阅量
     *
     * @return number[]  图书馆借阅量综合统计信息。目前仅含max:最大借阅量 ,avg:平均借阅量
     * @throws \EasyUtils\CustomBiz\Forward\Service\ForwordProxyException
     */
    public function getLibLoanCntData() 
    {
        return [];
    }

    /**
     * 返回指定应还时间段的所有当前借阅信息
     * @param string $reader_id        读者证号
     * @param string $starttime_str     应还日期查询开始值
     * @param string $endtime_str       应还日期查询结束值
     * @param int $page                 页码
     * @param int $page_size            分页记录数
     * @return []
     * @example return item
     * 'id' => 16938675,
     *  'reader_id' => 'Z0001755',
     *  'barcode' => '011057251',
     *  'bookname' => '轻松幽默侃唐朝·潜龙在渊',
     *  'search_no' => 'K242.09/13',
     *  'borrow_time' => '2019/6/5 09:51:07',
     *  'return_time' => '2019/9/16 00:00:00',
     *  'real_return_time' => '',
     *  'staff' => 'cir11',
     *  'state' => '0',
     */
    public function currentLendListByReturnTime($reader_id='', $page=1, $page_size=10, $starttime_str='', $endtime_str='')
    {
        $model = $this->getModel('BookOverdue');
        $query = $model->where('aid', $this->getAid())
            ->field('*')->where("status=0");   //状态 0在借中 1 已归还
        if ($reader_id) {
            $query = $query->where("reader_id='{$reader_id}'");
        }
        if ($starttime_str) {
            $starttime_str = date('Y-m-d', strtotime($starttime_str));
            $query = $query->where("return_date>='$starttime_str'");
        }
        if ($endtime_str) {
            $endtime_str = date('Y-m-d', strtotime($endtime_str));
            $query = $query->where("return_date<='$endtime_str'");
        }

        $offset = ($page - 1) * $page_size;
        $page_size = $page_size ?: 10;
        $list = $query->order('id', 'desc')->limit($offset, $page_size)
            ->select();

        if (!$list) {
            return $list;
        }
        //保持和前置机接口模式获取数据结果一致
        foreach ($list as &$item) {
            $item = [
//                'id' => $item['id'],
                'reader_id' => $item['reader_id'],
                'reader_name' => $item['name'],
                'barcode' => $item['barcode'],
                'title' => $item['title'],
                'search_no' => '',
                'borrow_time' => '',  //借书时间
                'return_time' => date('Y-m-d H:i:s', strtotime($item['return_date'])),  //应还时间
                'real_return_time' => '',  //实际归还时间
                'state' => $item['status'],  //状态 0在借中 1 已归还
            ];
        }

        return $list;
    }

    public function currentLendListByRealReturnTime($reader_id, $page = 1, $page_size = 10, $starttime_str = '', $endtime_str = '')
    {
        $aid = $this->getAid();
        $model = $this->getModel('CirculationLog', 'model', "library_data_{$aid}");
        $query = $model->field('*')->where("type=2");   //状态 1在借中 2已归还
        if ($reader_id) {
            $query = $query->where("reader_id='{$reader_id}'");
        }
        if ($starttime_str) {
            $query = $query->where("opt_time>='{$starttime_str}'");
        }
        if ($endtime_str) {
            $query = $query->where("opt_time<='{$endtime_str}'");
        }

        $offset = ($page - 1) * $page_size;
        $page_size = $page_size ?: 10;
        $list = $query->order('id', 'desc')->limit($offset, $page_size)->select();

        if (!$list) {
            return $list;
        }
        //保持和前置机接口模式获取数据结果一致
        foreach ($list as &$item) {
            $item = [
//                'id' => $item['id'],
                'reader_id' => $item['reader_id'],
                'reader_name' => $item['reader_name'],
                'barcode' => $item['barcode'],
                'title' => $item['title'],
                'search_no' => '',
                'borrow_time' => '',  //借书时间
                'return_time' => '',  //应还时间
                'real_return_time' => $item['opt_time'],  //实际归还时间
                'state' => $item['type'] == 1 ? 0 : 1,  //状态 0在借中 1 已归还
            ];
        }

        return $list;

    }

    public function currentLendListByBorrowTime($reader_id, $page = 1, $page_size = 10, $starttime_str = '', $endtime_str = '')
    {
        $aid = $this->getAid();
        $model = $this->getModel('CirculationLog', 'model', "library_data_{$aid}");
        $query = $model->field('*')->where("type=1");   //状态 1在借中 2已归还
        if ($reader_id) {
            $query = $query->where("reader_id='{$reader_id}'");
        }
        if ($starttime_str) {
            $query = $query->where("opt_time>='{$starttime_str}'");
        }
        if ($endtime_str) {
            $query = $query->where("opt_time<='{$endtime_str}'");
        }

        $offset = ($page - 1) * $page_size;
        $page_size = $page_size ?: 10;
        $list = $query->order('id', 'desc')->limit($offset, $page_size)->select();

        if (!$list) {
            return $list;
        }
        //保持和前置机接口模式获取数据结果一致
        foreach ($list as &$item) {
            $item = [
//                'id' => $item['id'],
                'reader_id' => $item['reader_id'],
                'reader_name' => $item['reader_name'],
                'barcode' => $item['barcode'],
                'title' => $item['title'],
                'search_no' => '',
                'borrow_time' => $item['opt_time'],  //借书时间
                'return_time' => '',  //应还时间
                'real_return_time' => '',  //实际归还时间
                'state' => $item['type'] == 1 ? 0 : 1,  //状态 0在借中 1 已归还
            ];
        }

        return $list;
    }


}