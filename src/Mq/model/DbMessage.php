<?php
/*
 * 与当前消息队列任务 强关联的DB 模型
 *
 * DbMessage.php
 * 2019-02-15 guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Mq\model;

use think\Model;

class DbMessage extends Model
{
    public function __construct($data = [])
    {
        //优先使用自定义的database配置
        $database = config('message.database');
        if ($database) {
            $this->connection = $database;
        }
        parent::__construct($data);
    }

    private function getTablePre() {
        return '';
    }

    /**
     * 搜索列表
     * @param int       $subject        分类id
     * @param string    $sortField     排序字段
     * @param string    $sort          排序规则（asc：从低到高，desc：从高到低）
     * @param int       $curPage        当前页
     * @param int       $pageSize       每页条数
     * @return array
     */
    public function pagelist($subject, $cur_page=1, $page_size=10)
    {
        $offset = ($cur_page - 1) * $page_size;
        $where = [];

        $query = $this->getUnionTableBuilder($subject, $where);
        $query->where($where);

        $datalist = $query->limit($offset, $page_size)->order('id', 'desc')->field(self::DEFAULT_LIST_FIELDS)->select()->toArray();

        //获取总条数
        $sql = $this->getLastSql();
        $sql = substr($sql, 0, strpos($sql, 'ORDER BY'));
        $sql = "SELECT count(1) AS cnt FROM ($sql) AS t" ;
        $total = ($this->query($sql))[0]['cnt'];
//        //总页数
        $page_count = ceil($total / $page_size);

        array_walk($datalist, [$this, 'wrapItem']);
        return compact('datalist','page_count', 'total');
    }

    public function wrapItem(&$item) {
        return $item;
    }

    /**
     * 根据主题，选择所有分表合并查询
     * @param $subject
     * @param $where
     * @return []
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function countItem($subject, $where)
    {
        $query = $this->getUnionTableBuilder($subject, $where);
        return $query->where($where)->count();
    }

    public function countWhere($subject)
    {
        $where = [];

        $query = $this->getUnionTableBuilder($subject, $where);
        $query->where($where);

        $sql = $query->limit(0, 1)->field(self::DEFAULT_LIST_FIELDS)->fetchSql()->select();
        $sql = substr($sql, 0, strpos($sql, 'LIMIT '));
        $sql = "SELECT count(1) AS cnt FROM ($sql) AS t" ;

        return ($this->query($sql))[0]['cnt'];
    }

    public function insertItem($subject, $data)
    {
        $query = $this->getUnionTableBuilder($subject);
        return $query->insertGetId($data);
    }

    /**
     * 根据主题，选择所有分表合并查询
     * @param $subject
     * @param $where
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateItem($subject, $where, $data)
    {
        $query = $this->getUnionTableBuilder($subject, $where);
        return $query->where($where)->update($data);
    }

    /**
     * 根据主题，选择所有分表合并查询
     * @param $subject
     * @param $where
     * @return []
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function findItem($subject, $where)
    {
        $query = $this->getUnionTableBuilder($subject, $where);
        return $query->where($where)->find();
    }

    /**
     * 根据主题，选择所有分表合并查询
     * @param $subject
     * @param $where
     * @return []
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function selectItem($subject, $where)
    {
        $query = $this->getUnionTableBuilder($subject, $where);
        $res = $query->where($where)->select();
        !is_array($res) && $res = $res->toArray();
        return $res;
    }

    public function getUnionTableBuilder($subject, $where=[]) {
        $exist_tables = $this->listSubjectTables($subject);
        $has_used_union = false;
        empty($visit_time_end) && $visit_time_end = time();
        $query = $this;
        foreach ($exist_tables as $table) {
            $table = current($table);
            if (!$has_used_union) {
                $query = $query->table($table);
                $has_used_union = true;
            } else {
//                    $query = $query->unionAll("SELECT * FROM {$table}");
                $query = $query->unionAll(function ($query) use ($table, $where) {
                    $query->field('*')->table($table)->where($where);
                });
            }
        }

        return $query;
    }

    public function getUnionTableSql($subject, $where_str='1=1') {
        $exist_tables = $this->listSubjectTables($subject);
        $has_used_union = false;
        $sql = "";
        foreach ($exist_tables as $table) {
            if (!$has_used_union) {
                $sql .= "SELECT * FROM {$table} WHERE {$where_str}";
                $has_used_union = true;
            } else {
                $sql .=" UNION ALL SELECT * FROM {$table}  WHERE {$where_str}";
            }
        }
        return $sql;
    }

    public function initTable($subject)
    {
        $table = $this->getTableName($subject);
        return $this->createTableIfNotExist($table);
    }

    /**
     * 创建指定名称的主题分表
     * @param $table
     * @return bool|mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function createTableIfNotExist($table) {

        if ($this->existTable($table)) {
            return true;
        }
        $sql = <<<___
            CREATE TABLE `{$table}` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
--               `msg_sn` bigint(14) NOT NULL COMMENT '消息唯一序列码',
              `batch_id` bigint(20) NOT NULL COMMENT '批次号，将多个消息归到一批。以便后台查询',
              `biz_id` varchar(32) DEFAULT NULL COMMENT '业务id，深度定制业务时用，可省略。如：aid',
              `biz_param` varchar(1024) DEFAULT NULL COMMENT '后台展示用的关键参数key-val列表',
              `consume_err_times` tinyint(2) UNSIGNED DEFAULT '0' COMMENT '消费任务错误次数',
              `item_hold_day` smallint(5) UNSIGNED DEFAULT '0' COMMENT '成功记录保留天数',
              `create_time` int(10) NOT NULL COMMENT '创建时间',
              `update_time` int(10) NOT NULL COMMENT '更新时间',
              `status` tinyint(1) DEFAULT '0' COMMENT '状态：0 等待添加到消息队列 1发送到消息队列 2 消息正在处理 3 消息处理成功 4消息处理失败',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
___;
        return self::query($sql);
    }

    /**
     * 判断分表是否已存在（创建）
     * @param $table
     * @return bool
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function existTable($table) {
        $res = $this->query("show tables like '{$table}' ");
        return $res ? true : false;
    }

    public function listSubjectTables($subject) {
        $table_like = $this->getTablePre() . "db_message_{$subject}";
        if ($subject) {
            return [[$table_like]];
        }

        $res = $this->query("show tables like '{$table_like}%' ");
        return $res;
    }

    /**
     * 根据消费主题，获取记录应该存入的分表名称
     * @param $subject
     * @param $timestamp
     * @return string
     */
    public function getTableName($subject) {
        $table = "db_message_{$subject}";
        return $this->getTablePre() . $table;
    }

    /**
     * 用户到访次数最大值与平均值
     * @param $subject
     * @param $visit_time_start
     * @param $visit_time_end
     * @param array $cacheOpt
     * @return array|false|mixed|string
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function statAccessMaxAvgTimes($subject, $visit_time_start, $visit_time_end, $cacheOpt = ['_cacheTime'=> 8640000])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
        }

        $union_table_sql = $this->getUnionTableSql($subject, $visit_time_start, $visit_time_end);
        $sql = "
          SELECT MAX(mycount) as maxcount,AVG(mycount) as avgcount 
          FROM ( 
               SELECT card_id, COUNT(card_id) as mycount
               FROM ({$union_table_sql}) as ut 
               GROUP BY card_id
          )  as table_b
          ";
        $max_avg_data = $this->query($sql);
        $data = [
            'max' => $max_avg_data[0]['maxcount'],
            'avg' => round($max_avg_data[0]['avgcount'], 2),
        ];
        return $data;
    }
}
