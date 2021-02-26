<?php
/*
 * 接口返回状态码常量类
 *
 * CodeConst.php
 * 2019年1月15日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 * 这里定义了接口返回状态码类常量
 * 请注意新定义的常量值，保持一定规范：在上一个定义常量基础上顺序加1
 */
namespace EasyUtils\Kernel\constant;

class ApiCodeConst extends AbstractCodeConst
{
    // +----------------------------------------------------------------------
    // | 业务相关码 0-100
    // +----------------------------------------------------------------------
    // 业务处理错误
    const BIZ_SUCCESS = 0;
    // 业务处理错误
    const BIZ_ERR = 11;
    // 记录已存在
    const ITEM_EXIST = 12;
    // 记录不存在
    const ITEM_NOT_EXIST = 13;
    // 请求参数错误
    const REQ_PARAM_ERR = 14;
    // 数据库操作失败
    const DB_ERR = 15;
    // 超过最大数设定
    const MAX_EXCEED_ERR = 16;
    // 小于最小数设定
    const MIN_LOWER_ERR = 17;

    // +----------------------------------------------------------------------
    // | 系统基本错误码 101-200
    // +----------------------------------------------------------------------
    // 系统错误
    const SYS_ERR = 101;
    // 系统繁忙
    const SYS_BUSY_ERR = 102;
    // 网络请求失败
    const NETWORK_ERR = 103;
    // 系统超时
    const SYS_OVERTIME_ERR = 104;

    // +----------------------------------------------------------------------
    // | api请求鉴权相关 201-300
    // +----------------------------------------------------------------------
    // 用户已关注公众号
    const WE_SUBCRIBED_ERR = 201;
    // 无效的OPENID
    const OPENID_INVALID_ERR = 202;
    // 不合法的KEY
    const KEY_ERR = 203;
    // 签名失败
    const SIGN_ERR = 204;

    // token未知错误
    const TOKEN_ERR = 210;
    // token过期
    const TOKEN_EXPIRED_ERR = 211;
    // token无效
    const TOKEN_INVALID_ERR = 212;


    /**
     * 所有常量值与常量文字描述映射表
     *
     * @var array $constsMap name=>tips
     */
    protected static $constsMap = [

        self::BIZ_SUCCESS           => '成功',
        self::BIZ_ERR               => '操作失败',
        self::ITEM_EXIST            => '记录已存在',
        self::ITEM_NOT_EXIST        => '记录不存在',
        self::REQ_PARAM_ERR         => '请求参数错误',
        self::DB_ERR                => '数据库操作失败',
        self::MAX_EXCEED_ERR        => '超过设定的最大数',
        self::MIN_LOWER_ERR         => '小于设定的最小数',

        self::SYS_ERR               => '系统错误',
        self::SYS_BUSY_ERR          => '系统繁忙',
        self::NETWORK_ERR           => '网络请求失败',
        self::SYS_OVERTIME_ERR      => '系统超时',

        
        self::WE_SUBCRIBED_ERR      => '用户已关注公众号',
        self::OPENID_INVALID_ERR    => '无效的OPENID',
        
        self::KEY_ERR               => '不合法的KEY',
        self::SIGN_ERR              => '签名失败',
        self::TOKEN_ERR             => '未知token错误',
        self::TOKEN_EXPIRED_ERR     => 'token已过期',
        self::TOKEN_INVALID_ERR     => 'token无效',
    ];
    
}
