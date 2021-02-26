<?php
//字符串处理函数

use EasyUtils\Kernel\Support\Str;

/**
 * 文本串标点符号过滤
 *
 * @author: xuyuqiong@bookgoal.com.cn
 * 
 * @param string $text           - 需要过滤标点符号的字符串
 *
 * @return string $text  - 过滤后的字符串
 */
function filter_mark($text){ 
	if(trim($text) == '') return '';
	//$text = preg_replace("/[[:punct:]\s]/",' ',$text);
	$text=urlencode($text);
	$text=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99|%EF%BD%9E|%EF%BC%8E|%EF%BC%88)+/",'',$text);
	$text=urldecode($text);
	return trim($text);
}

/**
 * 移除字符串bom头
 * @param $str
 * @return mixed
 */
function clear_bom($str){
    return Str::clearBom($str);
}

if (! function_exists ( 'do_order_sn' )) {
    /*
     * 生成交易流水号
     * @param char(2) $type
     */
    function do_order_sn($type='0'){
        return date('ymdHis',time()).rand(1000,9999);
    }
}

function generate_id($len=14){
    $s = str_replace('.', '', microtime(true));
    return substr($s, 0, $len-1);
}

function str_exist($haystack, $needle, $offset = 0, $or_relation=true) {
    is_string($needle) && $needle = [$needle];
    $last_res = $or_relation ? false : true;    //上一次结果
    foreach ($needle as $_needle) {
        $current_res = false !== strpos($haystack, $_needle, $offset);
        $last_res = $current_res = $or_relation ? ($current_res || $last_res) : ($current_res && $last_res);
    }

    return $current_res;
}


function replace_url_param($url, $param_list)
{
    foreach ($param_list as $key => $val) {
        if (false !== strpos($url, "{$key}=") ) {
            $url = preg_replace('/__aid=([^&]+)/i', "{$key}={$val}", $url);
        } else {
            $url .= (strpos($url, '?')>0 ? '&' : '?') . "__aid={$val}";
        }
    }

    return $url;
}

function mb_substr_replace($words, $length = 20, $replace = '..')
{
    if (mb_strlen($words) > $length) {
        $words = mb_substr($words, 0, ($length - mb_strlen($replace))).$replace;
    }
    return $words;
}

/**
 * 下划线转驼峰
 * 思路:
 * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
 * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
 */
function camelize($uncamelized_words, $separator = '_')
{
    $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
}

/**
 * 驼峰命名转下划线命名
 * 思路:
 * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}