<?php
namespace EasyUtils\Kernel\Support;

class Str
{
    /**
     * 移除字符串bom头
     * @param $str
     * @return mixed
     */
    public static function clearBom($str){
        $bom = chr(239).chr(187).chr(191);
        return str_replace($bom ,'',$str);
    }

    /**
     * 生成随机字符串
     * @param int $length 生成随机字符串的长度
     * @param string $char 组成随机字符串的字符串
     * @return string $string 生成的随机字符串
     */
    public static function getRandStr($length = 32, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_')
    {
        if (!is_int($length) || $length < 0) {
            return false;
        }

        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }

        return $string;
    }

    /**
     * 把ID字符串转换成对应的名称字符串
     * @param array|string $ids ID传
     * @param array $map
     * @param string $glue
     * @return string
     */
    public static function IdsToNames($ids, $map, $glue = ',')
    {
        $ids = is_array($ids) ? $ids : explode($glue, $ids);
        foreach ($ids as $key => $val) {
            $ids[$key] = $map[$val];
        }
        return implode($glue, $ids);
    }

    /**
     * 静态方法调用
     * @access public
     * @param  string $method 调用方法
     * @param  mixed  $args   参数
     * @return void
     */
    public static function __callStatic($method, $args)
    {
        //转发到str_helper.php
    }

}
