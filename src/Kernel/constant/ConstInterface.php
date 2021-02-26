<?php
/*
 * 常量两种类型类的接口类。保持取常量码和对应提示语返回格式的统一
 *
 * ConstInterface.php
 * 2019年1月26日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Kernel\constant;

/**
 * 抽象常量类
 * - 请注意保证所有常量值唯一
 */
interface ConstInterface
{
    /**
     * 获取常量值对应的项.
     *
     * @param  int    $const_val     常量值
     * @return array
     */
    public static function get($const_val);
}
