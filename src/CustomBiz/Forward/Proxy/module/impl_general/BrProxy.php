<?php
/*
 * 图书借阅模块代理的通用实现类
 *
 * BrProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书借阅业务模块的代理接口的通用实现
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_general;

use EasyUtils\Kernel\Traits\SingletonTrait;
use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IBrProxy;

/**
 * 图书借阅模块代理的通用实现类
 */
class BrProxy extends GeneralImplBase implements IBrProxy
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
        $params = [
            'aid' => $this->aid,
        ];
        $res = $this->setErrorRetryTimes(2)
            ->request('libsys/br/getLibLoanCntData', $params, 8);

        // 原样返回接口成功数据
        $res = [
            'total'          => $res['data']['total'],
            'reader_cnt'     => $res['data']['reader_cnt'],
            'max_loan_times' => $res['data']['max_loan_times'],
            'avg_loan_times' => $res['data']['avg_loan_times'],
        ];
        return $res;
    }

    /**
     * 返回指定应还时间段的所有当前借阅信息
     * @param string $reader_id        读者证号
     * @param string $starttime_str     应还日期查询开始值
     * @param string $endtime_str       应还日期查询结束值
     * @param int $page                 页码
     * @param int $page_size            分页记录数
     * @return []
     */
    public function currentLendListByReturnTime($reader_id='', $page=1, $page_size=10, $starttime_str='', $endtime_str='')
    {
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
            'starttime' => $starttime_str,
            'endtime'   => $endtime_str,
            'page'      => $page,
            'size'      => $page_size,
            'time_type' => 'return',
        ];
        $res = $this->setErrorRetryTimes(2)->request('libsys/br/currentLendList', $params, 20);

        // 原样返回接口成功数据
        return $res['data'] ? $res['data'] : [];
    }

    /**
     * 返回指定应还时间段的所有当前借阅信息
     * @param string $reader_id        读者证号
     * @param string $starttime_str     应还日期查询开始值
     * @param string $endtime_str       应还日期查询结束值
     * @param int $page                 页码
     * @param int $page_size            分页记录数
     * @return []
     */
    public function currentLendListByRealReturnTime($reader_id='', $page=1, $page_size=10, $starttime_str='', $endtime_str='')
    {
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
            'starttime' => $starttime_str,
            'endtime'   => $endtime_str,
            'page'      => $page,
            'size'      => $page_size,
            'time_type' => 'real_return',
        ];
        $res = $this->setErrorRetryTimes(2)->request('libsys/br/currentLendList', $params, 20);

        // 原样返回接口成功数据
        return $res['data'] ? $res['data'] : [];
    }

    /**
     * 用户借书时间的 借阅信息列表
     * @param string $reader_id        读者证号
     * @param string $starttime_str     应还日期查询开始值
     * @param string $endtime_str       应还日期查询结束值
     * @param int $page                 页码
     * @param int $page_size            分页记录数
     * @return []
     */
    public function currentLendListByBorrowTime($reader_id='', $page=1, $page_size=10, $starttime_str='', $endtime_str='')
    {
        $params = [
            'aid'       => $this->aid,
            'reader_id' => $reader_id,
            'starttime' => $starttime_str,
            'endtime'   => $endtime_str,
            'page'      => $page,
            'size'      => $page_size,
            'time_type' => 'borrow',
        ];
        $res = $this->setErrorRetryTimes(2)->request('libsys/br/currentLendList', $params, 20);

        // 原样返回接口成功数据
        return $res['data'] ? $res['data'] : [];
    }
    
}