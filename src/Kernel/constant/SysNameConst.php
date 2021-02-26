<?php
/*
 * 博客业务系统名称码常量类
 *
 * SysNameConst.php
 * 2019年2月25日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 * 这里定义了接口返回状态码类常量
 * 请注意新定义的常量值，保持一定规范：在上一个定义常量基础上顺序加1
 */
namespace EasyUtils\Kernel\constant;

class SysNameConst extends AbstractCodeConst
{
    /**
     * 接口系统标致: 微信图书馆
     * @var string
     */
    const SYS_WXLIB = 'wxlib';

    /**
     * 接口系统标致: 微信图书馆
     * @var string
     */
    const SYS_WXLIB_V2 = 'wxlib_v2';

    /**
     * 接口系统标致: 图书馆系统
     * @var string
     */
    const SYS_LIBSYS = 'libsys';

    /**
     * 接口系统标致: 图书馆系统
     * @var string
     */
    const SYS_CLMS = 'clms';

    /**
     * 接口系统标致: 数据显示平台
     * @var string
     */
    const SYS_DATA = 'data';

    /**
     * 服务应用标致: 用户服务
     * @var string
     */
    const SVC_USER = 'svc_user';

    /**
     * 服务应用标致: 图书管理系统对接服务
     * @var string
     */
    const SVC_LMS = 'svc_lms';

    /**
     * 服务应用标致: 活动服务
     * @var string
     */
    const SVC_ACTIVITY = 'svc_activity';

    /**
     * 服务应用标致: 志愿者服务
     * @var string
     */
    const SVC_VOLUNTEER = 'svc_volunteer';

    /**
     * 服务应用标致: 积分服务
     * @var string
     */
    const SVC_POINT = 'svc_point';

    /**
     * 服务应用标致: 内容服务
     * @var string
     */
    const SVC_CONTENT = 'svc_content';

    /**
     * 所有常量值与常量文字描述映射表
     *
     * @var array $constsMap name=>tips
     */
    public static $constsMap = [
        self::SYS_WXLIB     => '微信图书馆',
        self::SYS_WXLIB_V2  => '微信图书馆2.0',
        self::SYS_LIBSYS    => '图书馆系统',
        self::SYS_CLMS      => '云图书管理系统',
        self::SYS_DATA      => '数据显示平台',
        self::SVC_USER      => '用户服务',
        self::SVC_LMS       => '图书管理系统服务',
        self::SVC_ACTIVITY  => '活动服务',
        self::SVC_VOLUNTEER => '志愿者服务',
        self::SVC_POINT     => '积分服务',
        self::SVC_CONTENT   => '内容服务',
    ];

}
