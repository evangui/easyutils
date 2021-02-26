<?php

namespace EasyUtils\Kernel\Support;

class IsbnUtil
{

    /**
     * 验证是否是正确的isbn
     * @return boolean
     */
    public static function isIsbn($isbn)
    {
        $isbn = self::kickIsbn($isbn);
        $len = strlen($isbn);
        //判断长度
        if ($len != 10 && $len != 13) {
            return false;
        }
        $rc = self::isbnCompute($isbn, $len);

        if ($isbn[$len - 1] != $rc)  {
            //ISBN尾数与计算出来的校验码不符
            return false;
        } else {
            return true;
        }
    }

    /**
     * 将isbn转换成9位isbn
     * @return boolean
     */
    public static function toIsbn9($isbn)
    {
        $isbn = self::kickIsbn($isbn);
        $len = strlen($isbn);
        //判断长度
        if ($len != 9 && $len != 10 && $len != 13) {
            return $isbn;
        }

        return substr($isbn, -10, 9);
    }

    /**
     * 将13位isbn转换成10位isbn
     * @return boolean
     */
    public static function toIsbn10($isbn)
    {
        $isbn = self::kickIsbn($isbn);
        $len = strlen($isbn);

        //判断长度
        if ($len != 13) {
            return $isbn;
        }
        $rc = self::isbnCompute($isbn, $len);
        return substr($isbn, 3, 9) . $rc;
    }

    /**
     * 计算ISBN末位校验码
     * return string
     */
    private static function isbnCompute($isbn, $len)
    {
        if ($len == 10) {
            $digit = 11 - self::isbnSum($isbn, $len) % 11;
            if ($digit == 10) {
                $rc = 'X';
            } else {
                if ($digit == 11) {
                    $rc = '0';
                } else {
                    $rc = (string)$digit;
                }
            }
        } else {
            if ($len == 13) {
                $digit = 10 - self::isbnSum($isbn, $len) % 10;
                if ($digit == 10) {
                    $rc = '0';
                } else {
                    $rc = (string)$digit;
                }
            }
        }

        return $rc;
    }
    /**
     * 计算ISBN加权和
     * return int
     */
    private static function isbnSum($isbn, $len)
    {
        $sum = 0;
        if ($len == 10) {
            for ($i = 0; $i < $len - 1; $i++) {
                $sum = $sum + (int)$isbn[$i] * ($len - $i);
            }
        } elseif ($len == 13) {
            for ($i = 0; $i < $len - 1; $i++) {
                if ($i % 2 == 0) {
                    $sum = $sum + (int)$isbn[$i];
                } else {
                    $sum = $sum + (int)$isbn[$i] * 3;
                }
            }
        }
        return $sum;
    }

    /**
     * 去除isbn空格和-
     * @return boolean
     */
    public static function kickIsbn($isbn)
    {
        $isbn = str_replace('-','',$isbn);//去除-
        $isbn = str_replace(' ','',$isbn);//去除空格
        return $isbn;
    }


}
