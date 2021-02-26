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
namespace EasyUtils\Kernel\Constant;

class WeixinConst extends AbstractCodeConst
{
    /**
     * 微图小程序名称、与配置中的名称一致
     * @var string
     */
    const WXAPP_NAME_WXLIB = 'wxlib';

    /**
     * 布狗阅读
     * @var string
     */
    const WXAPP_NAME_READ = 'read';

    /**
     * 文旅小程序名称
     * @var string
     */
    const WXAPP_NAME_CULTURE_TOUR = 'culture_tour';

    /**
     * 文化云小程序名称
     * @var string
     */
    const WXAPP_NAME_CULTURE = 'culture';

    /**
     * 领读者小程序名称、与配置中的名称一致
     * @var string
     */
    const WXAPP_NAME_LEAD = 'lead';


}
