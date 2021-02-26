<?php
/*
 * 抽象常量类文件
 *
 * AbstractConst.php
 * 2019年1月15日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 * 这里封装了基于业务模块的抽象常量类基本的核心get方法。
 * - 子类需要继承重新定义自己的类常量与$constsMap
 * - 请注意保证所有常量值唯一，否则文字提示可能不能正确定位
 * - 调用方式示例： 
 *      //同时获取格式统一的值与提示语
 *      $code = AbstractConst::get(AbstractConst::DEMO); 返回格式array(const_val, tips)
 *  - 该类定义方式不如MapConst简单灵活，不收限制。
 *    ## 建议：
 *     + 如需要为单个业务定制状态码，请用AbstractConst方式
 *     + 如需要为综合复杂的，可能涉及到多个业务进行综合常量定义时，可用类似该类的模式
 */
namespace EasyUtils\Kernel\constant;

/**
 * 抽象常量类
 * - 请注意保证所有常量值唯一
 */
class AbstractCodeConst implements ConstInterface
{
    /**
     * 示例类常量
     */
    const DEMO = 100;
    
    
    /**
     * 所有常量值与常量文字提示映射表
     * 如有需要，子类请重新定义该变量
     *  
     * @var array $constsMap name=>tips
     */
    protected static $constsMap = [
        self::DEMO => '示例提示',
    ];
    
    /**
     * 获取常量值对应的项.
     *
     * @param  int    $const_val     常量值
     * @return array  [code, tips]
     */
    public static function get($const_val)
    {
        if (!isset(static::$constsMap[$const_val])) {
            throw new \InvalidArgumentException('常量值 "'.$const_val.'" 未定义, 支持常量值: '.implode(', ', array_keys(static::$constsMap)));
        }
        
        return [$const_val, static::$constsMap[$const_val]];
    }
    
    /**
     * 获取所有类常量
     * @return array const names => const values.
     *          联合数组，键名为描述文字，值为对应的类常量值
     */
    public static function getConsts($flip=true) {
        return $flip ? array_flip(static::$constsMap) : static::$constsMap;
    }
    
    /**
     * 获取常量值对应的文字说明.
     *
     * @param  int    $const_val    常量值
     * @return string       常量文字说明
     */
    public static function getTips($const_val)
    {
        if (!isset(static::$constsMap[$const_val])) {
            throw new \InvalidArgumentException('常量值 "'.$const_val.'" 未定义, 支持常量值: '.implode(', ', array_keys(static::$constsMap)));
        }
        return static::$constsMap[$const_val];
    }
    
    /**
     * 将存在的常量名字符串，转换成常量值
     *
     * @param 存在的常量名字符串
     * @return int  常量值
     */
    public static function toConst($const_name)
    {
        if (is_string($const_name) && defined(__CLASS__.'::'.strtoupper($const_name))) {
            return constant(__CLASS__.'::'.strtoupper($const_name));
        }
        
        return $const_name;
    }
    
}
