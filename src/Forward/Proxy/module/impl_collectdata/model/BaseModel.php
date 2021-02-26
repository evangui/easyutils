<?php
/*
 * 前置机收集业务数据的db模型基类
 *
 * BaseModel.php
 * 2019-06-06  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Forward\Service\Proxy\module\impl_collectdata\model;

use think\Db;
use think\Model;

/**
 * @author guiyj007
 *        
 * 图书馆的前置机收集收集代理对象抽象类
 */
class BaseModel extends Model
{
    // 数据库配置
//    protected $connection = [];

    protected $table = '';

    public function __construct()
    {}

    public function initDb($db_conf)
    {
        $this->connection = $db_conf;
        return $this;
    }

    public function base(&$query)
    {
        $query = Db::connect($this->connection)->table($this->table);
    }
    
}
