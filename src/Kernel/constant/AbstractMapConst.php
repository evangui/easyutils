<?php
/*
 * 通用业务复杂的字典常量类文件
 *
 * MapConst.php
 * 2019年1月15日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 * 这里定义了通用业务的常量编码值与文字提示
 *  - 区别于继承 \app\Kernel\constant\AbstractConst的常量子项类（编码值必须唯一），
 *      本类用于定义综合业务常量（编码值可以相同）
 *  - 调用方式示例：
 *      //同时获取格式统一的值与提示语
 *      $code = AbstractConst::get(AbstractConst::DEMO); 返回格式array(const_val, tips)
 *  - 定义方式更加简单灵活，不收限制。但调用方式没有继承自AbstractConst的常量类方便。
 *    ## 建议：
 *      + 如需要为单个业务定制状态码，请用AbstractConst方式
 *      + 如需要为综合复杂的，可能涉及到多个业务进行综合常量定义时，可用类似该类的模式
 */
namespace EasyUtils\Kernel\constant;

class AbstractMapConst implements ConstInterface
{
    /**
     * 示例类常量，常量值与常量文字提示同时设置
     */
    const MAP_DEMO = [100, '数组常量示例提示'];

    /**
     * 获取常量值对应的项.
     *
     * @param  int    $const_val     常量值
     * @return array  [code, tips]
     */
    public static function get($const_val)
    {
        return $const_val;
    }
}
