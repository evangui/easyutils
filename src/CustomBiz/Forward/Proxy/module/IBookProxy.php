<?php
/*
 * 书籍模块代理的抽象类
 *
 * AbstractBookProxy.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 用于定义所有图书业务模块的接口规格
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module;

/**
 * @author guiyj007
 *    
 * 书籍模块代理的抽象类
 */
interface IBookProxy
{
    /**
     * 根据barcodes获取排架位置信息
     * @param $barcodes
     * @return mixed
     */
    public function getPositionByBarcodes($barcodes);
    
}