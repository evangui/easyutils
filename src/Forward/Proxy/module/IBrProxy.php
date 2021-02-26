<?php
/*
 * 图书借阅模块代理的抽象类
 *
 * AbstractBrProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 用于定义所有图书借阅业务模块的接口规格
 */
namespace EasyUtils\Forward\Service\Proxy\module;

/**
 * 图书借阅模块代理的抽象类
 */
interface IBrProxy
{
    /**
     * 获取指定图书馆的平均借阅量和最高借阅量
     * -这里暂并不定义为抽象方法。以减轻当增加新家族的bookproxy产品的工作量
     *
     * @return number[]  图书馆借阅量综合统计信息。目前仅含max:最大借阅量 ,avg:平均借阅量
     */
    public function getLibLoanCntData();

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
    public function currentLendListByReturnTime($reader_id='', $page=1, $page_size=10, $starttime_str='', $endtime_str='');

    /**
     * 用户实际还书时间段的 借阅信息列表
     * @param string $reader_id        读者证号
     * @param string $starttime_str     借书日期查询开始值
     * @param string $endtime_str       借书日期查询结束值
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
    public function currentLendListByRealReturnTime(
        $reader_id,
        $page=1,
        $page_size=10,
        $starttime_str='',
        $endtime_str=''
    );

    /**
     * 用户借书时间的 借阅信息列表
     * @param string $reader_id        读者证号
     * @param string $starttime_str     借书日期查询开始值
     * @param string $endtime_str       借书日期查询结束值
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
    public function currentLendListByBorrowTime(
        $reader_id,
        $page=1,
        $page_size=10,
        $starttime_str='',
        $endtime_str=''
    );

}