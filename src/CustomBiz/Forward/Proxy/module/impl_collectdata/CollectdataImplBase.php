<?php
/*
 * 通用的前置机代理对象实现用到的基本方法定义
 *
 * GeneralImplTrait.php
 * 2019-01-29 16:09  guiyj<guiyj007@gmail.com>
 *
 * 本文件定义的trait，用于给所有 通用的前置机模块代理对象，在实现时增加基本功能方法
 * 可作为基类用途使用
 */
namespace EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_collectdata;

/**
 * @author guiyj007
 *        
 * 图书馆的前置机收集收集代理对象抽象类
 */
abstract class CollectdataImplBase
{
    protected $aid;

    /**
     * 创建具体代理对象时的初始操作
     * @param $aid
     * @return $this
     */
    public function initProxy($aid)
    {
        $this->aid = intval($aid);
        return $this;
    }

    /**
     * 获取db数据模型入口方法
     * @param string $name
     * @param string $layer
     * @param bool $appendSuffix
     * @return \think\Model
     */
    public function getModel($name = '', $layer = 'model', $db_name = 'default')
    {
        $forward_conf = $this->getGeneralConf();
        $database_conf = !empty($forward_conf['database']) ? $forward_conf['database'] : config("collectdata_db.{$db_name}");

        $name = '\EasyUtils\CustomBiz\Forward\Service\Proxy\module\impl_collectdata\model\\' . $name;
        return model($name, $layer, false)->initDb($database_conf);
    }

    protected function getAid()
    {
        return $this->aid;
    }


    /**
     * 获取默认通用代理方法的前置机接口请求地址
     *
     * @param string $aid
     * @return string
     */
    public function getGeneralConf()
    {
        return config('forword_proxy.' . $this->aid);
    }

}
