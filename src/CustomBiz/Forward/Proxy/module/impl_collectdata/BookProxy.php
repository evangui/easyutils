<?php
/*
 * 书籍模块代理的通用实现类
 *
 * BookProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书业务模块代理接口的通用实现
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_collectdata;

use EasyUtils\CustomBiz\Forward\Service\Proxy\module\IBookProxy;
use EasyUtils\Kernel\Traits\SingletonTrait;

/**
 * 书籍模块代理的通用实现类
 */
class BookProxy extends CollectdataImplBase implements IBookProxy
{
    use SingletonTrait;

    /**
     * 根据barcode获取排架位置信息
     * {@inheritDoc}
     */
    public function getPositionByBarcodes($barcodes, $cacheOpt = ['_cacheTime'=> 1800])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            $keyarr = [$barcodes, $this->aid];
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt, $keyarr);
        }

        is_array($barcodes) && $barcodes = implode(',', $barcodes);
        $model = $this->getModel('BookPosition', 'model', 'bookgoal_book');
        $where = [
            'aid' => $this->aid,
        ];
        $list = $model->where($where)->whereIn('barcode', $barcodes)->field('barcode,position_desc as pos')->order('id', 'desc')->select();
        return ['data'=>$list];
    }
}
