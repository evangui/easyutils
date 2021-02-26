<?php

namespace EasyUtils\Kernel\Support;

class FriendlyDisplay
{

    /**
     * 阿拉伯数字转中文
     * @param $num
     * @return mixed|string
     */
    public static function numberToChinese($num)
    {
        if (is_int($num) && $num < 100) {
            $char = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
            $unit = ['', '十', '百', '千', '万'];
            $return = '';
            if ($num < 10) {
                $return = $char[$num];
            } elseif ($num % 10 == 0) {
                $firstNum = substr($num, 0, 1);
                if ($num != 10) $return .= $char[$firstNum];
                $return .= $unit[strlen($num) - 1];
            } elseif ($num < 20) {
                $return = $unit[substr($num, 0, -1)] . $char[substr($num, -1)];
            } else {
                $numData = str_split($num);
                $numLength = count($numData) - 1;
                foreach ($numData as $k => $v) {
                    if ($k == $numLength) continue;
                    $return .= $char[$v];
                    if ($v != 0) $return .= $unit[$numLength - $k];
                }
                $return .= $char[substr($num, -1)];
            }
            return $return;
        }
    }

    /**
     * 数字转星期几
     * @param $num
     * @return mixed|string
     */
    public static function numberToWeekName($num){
        if (is_int($num) && $num <= 7 && $num>0) {
            $char = array('一', '二', '三', '四', '五', '六', '日');
            return $char[$num-1];
        }
        return '';
    }

    /**
     * 工作日显示风格1
     * @param $str
     * @return string
     */
    public static function showWeekStyle1($str)
    {
        $arr = explode(',', $str);
        $arr = (array_map('intval', $arr));
        if (count($arr) == 0) {
            return '';
        }
        if (count($arr) == 1) {
            return '周' . self::numberToWeekName($arr[0]);
        }
        ksort($arr);
        $count = count($arr);
        $min = $arr[0];
        $max = $arr[$count - 1];
        $flag = true;
        for ($i = 0; $i < $count; $i++) {
            if (!empty($arr[$i + 1]) && ($arr[$i + 1] - $arr[$i] > 1)) {
                $flag = false;
            }
        }
        if ($flag == true) {
            return sprintf('周%s到周%s', self::numberToWeekName($min), self::numberToWeekName($max));
        }
        $strs = [];
        for ($i = 0; $i < $count; $i++) {
            $strs[] = sprintf('周%s', self::numberToWeekName($arr[$i]));
        }
        return join(',', $strs);


    }
}