<?php
/*
 * 图书管配置综合管理类
 *
 * LibConf.php
 * 2019-03-19 11:41  guiyj<guiyj007@gmail.com>
 *
 * 用于将图书馆各种配置进行综合管理
 */
namespace EasyUtils\Kernel\Support;

use EasyUtils\Apibase\RpcFactory;

/**
 * 图书管配置综合管理类
 */
class LibConf
{
    //汇文、图创、成蹊、ilas 关键标识符常量定义
    const SYS_ILIB     = 'ilib';
    const SYS_INTERLIB = 'interlib';
    const SYS_CHENGXI  = 'chengxi';
    const SYS_ILAS     = 'ilas';
    const SYS_CLMS     = 'clms';
    const SYS_SIP2     = 'sip2';

    //汇文、图创、成蹊、ilas、博库云图书管理系统 的 图书管理系统已支持aid列表
    public static $sysFamilyAidMap = [
        self::SYS_ILIB => [3, 10, 11, 12],
        self::SYS_INTERLIB => [3001, 3003, 3004, 3005, 3006, 3008, 3009, 3010, 3012,14],
        self::SYS_CHENGXI => [4, 3027],
        self::SYS_ILAS => [8],
        self::SYS_CLMS => [1001],
        self::SYS_SIP2 => ['sip2_3022'],
    ];

    public static $aidSublocalMap = [
        3006 => ['江夏区金港分馆'],
    ];

    public static $aidOrglibMap = [
        3006 => '1501',
    ];

    //超级管理员读者证号
    public static $aidSuperReaderid= [
        3006 => '42011501999',  //19800510
    ];

    /**
     * libcode与aid映射
     * @var array
     */
    public static $libCodeAidMap = [
        'WT' => '3005',
        'WHST' => '3100',
        'WHDT' => '3100',
        'JASRG' => '3100',
    ];


    /**
     * 根据aid识别图书馆所用系统。返回系统英文标识，建议用本类宏
     * @param integer $aid     图书馆aid
     * @return string  LibConf::SYS_ILIB | LibConf::SYS_INTERLIB | LibConf::SYS_CHENGXI
     */
    public static function getLibSysCode($aid)
    {
        $aid = strval($aid);
        $ret = '';
        
        //根据aid识别图书馆所用系统是汇文还是图创系统
        foreach (self::$sysFamilyAidMap as $sys_name => $aid_arr) {
            if (in_array($aid, $aid_arr)) {
                return $sys_name;
            }
        }
        //默认返回图创系统标识名
        return self::SYS_INTERLIB;
    }

    /**
     * 获取客户信息
     * @param $aid
     * @param array $cacheOpt
     * @return mixed
     */
    public static function getCustomerByAid($aid, $cacheOpt = ['_cacheTime'=> 180])
    {
        $customer = self::weConf($aid, '', $cacheOpt);
        return $customer;
    }

    /**
     * 获取图书馆的微信配置参数
     * @param $aid
     * @param array $cacheOpt
     * @return mixed
     */
    public static function weConf($aid, $appid='', $cacheOpt = ['_cacheTime'=> 180])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
        }
        $res = req_wxlib_v2('weixin/we.general/config', [
            'aid'   => $aid,
            'appid' => $appid,
        ]);
        return $res['data'];
    }

    /**
     * 获取图书馆的微信配置参数
     * @param $aid
     * @param array $cacheOpt
     * @return mixed
     */
    public static function wxConf($aid, $app_code = '', $cacheOpt = ['_cacheTime'=> 180])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
        }
        $res = req_wxlib_v2('weixin/we.general/appConfig', [
            'aid'   => $aid,
            'app_code' => $app_code,
        ]);
        return $res['data'];
    }

    /**
     * 识别馆藏地，是否是分馆地址
     * @param $aid
     * @param $local_name
     * @return bool
     */
    public static function isSubLibLocal($aid, $local_name) {
        if (empty(self::$aidSublocalMap[$aid])) {
            return false;
        }
        $map = self::$aidSublocalMap[$aid];
        return in_array($local_name, $map);
    }

    /**
     * 根据aid获取所在馆的映射关系行数据
     * @param $aid
     * @param int $expected_general_aid
     * @return array aid,general_aid,remark
     */
    public static function getLibMap($aid, $cacheOpt = ['_cacheTime'=> 300])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
        }
        $res= RpcFactory::user()->customer->getLibMap($aid);
        return isset($res['data']) ? ($res['data']) : [];
    }

    /**
     * 获取图书馆超级读者的读者证号码
     * @param $aid
     * @param $local_name
     * @return bool
     */
    public static function getSuperReaderid($aid) {
        if (empty(self::$aidSuperReaderid[$aid])) {
            return false;
        }
        return self::$aidSuperReaderid[$aid];
    }

    /**
     * 获取分馆的业务系统图书馆编号
     * @param $aid
     * @return bool|mixed
     */
    public static function getOrglib($aid) {
        if (empty(self::$aidOrglibMap[$aid])) {
            return false;
        }
        return self::$aidOrglibMap[$aid];
    }

    /**
     * 是否为武汉市区馆,根据aid判断
     */
    public static function isWhLib($aid)
    {
        $lib = self::getLibMap($aid);
        if(isset($lib['general_aid']) && $lib['general_aid'] == 3005) {
            return true;
        }
        return false;
    }

    /**
     * 根据libcode获取对应aid
     */
    public static function getAidByLibcode($libcode)
    {
        return isset(self::$libCodeAidMap[$libcode]) ? self::$libCodeAidMap[$libcode] : 0;
    }

    /**
     * 获取图书馆模块化配置。
     * 以后需要兼容管理后台设置与配置文件设置
     *
     * @param $aid
     * @param $module
     * @param $key
     * @param null $default_val
     * @param int $cache_time
     * @return |null
     */
    public static function get($aid, $module, $key='', $default_val = null, $cache_time = 3600)
    {
        if ('we' == $module) {
            return self::weConf($aid, '', $cache_time);
        }

        $aid = trim($aid);

        $lib_conf = config("library.lib_{$aid}");
        if (!isset($lib_conf[$module][$key]) && null !== $default_val) {
            return $default_val;
        }
        if (empty($key)) {
            return $lib_conf[$module];
        } else {
            return $lib_conf[$module][$key];
        }
    }
}
