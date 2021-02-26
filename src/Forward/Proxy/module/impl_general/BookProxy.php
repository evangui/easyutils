<?php
/*
 * 书籍模块代理的通用实现类
 *
 * BookProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 实现所有图书业务模块代理接口的通用实现
 */
namespace EasyUtils\Forward\Service\Proxy\module\impl_general;

use EasyUtils\Forward\Service\Proxy\module\IBookProxy;
use EasyUtils\Kernel\traits\SingletonTrait;

/**
 * 书籍模块代理的通用实现类
 */
class BookProxy extends GeneralImplBase implements IBookProxy
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
        
        $params = [
            'aid' => $this->aid,
            'barcode' => $barcodes,
		];
        $res = $this->request('libsys/book/getPosition', $params);
        if (!isset($res['code'])) {
            //网络请求失败，尝试重请求一次
            $res = $this->request('libsys/book/getPosition', $params);
        }
        
        // 原样返回接口成功数据
        return $res;
    }
}
