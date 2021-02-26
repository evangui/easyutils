<?php
/**
 * MessageStatistics.php
 * 2020-02-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\MessageSender\model;


use think\Model;

class MessageStatistics extends Model
{
    public function __construct($data = [])
    {
        //优先使用自定义的database配置
        $database = config('database.bg_wxlib');
        if ($database) {
            $this->connection = $database;
        }
        parent::__construct($data);
    }
}