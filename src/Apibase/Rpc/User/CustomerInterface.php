<?php
/*
 * 客户（商户） 服务方法接口
 *
 * CustomerInterface.php
 * 2020-03-23 guiyj007@gmail.com
 *
 * 用于rpc服务类，rpc客户端调用方法类的接口定义
 */
namespace EasyUtils\Apibase\Rpc\User;


/**
 * 
 */
interface CustomerInterface
{

    /**
     * 根据来源馆aid，获取aid同一个系统内其他aid,以及图书馆的名字
     *
     * 用于绑定读者证页面
     * @param int $from_aid    当前入口的图书馆的编号
     * @param int $relate_type
     * @return array
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function listRelatedLibs($from_aid, $relate_type = 0);
}

