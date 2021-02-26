<?php
/*
 * 图书配置综合管理类
 *
 * BookConf.php
 * 2019-03-19 11:41  guiyj<guiyj007@gmail.com>
 *
 * 用于将图书馆各种配置进行综合管理
 */
namespace EasyUtils\Kernel\Support;

/**
 * 图书配置综合管理类
 */
class BookConf
{
    //汇文、图创、成蹊、ilas 中图法分类主分类标识符与名称映射定义
    const CODEN_NAME_MAP = [
        'A' =>  '马克思主义、列宁主义、毛泽东思想、邓小平理论',
        'B' =>  '哲学、宗教',
        'C' =>  '社会科学总论',
        'D' =>  '政治、法律',
        'E' =>  '军事',
        'F' =>  '经济',
        'G' =>  '文化、科学、教育、体育',
        'H' =>  '语言、文字',
        'I' =>  '文学',
        'J' =>  '艺术',
        'K' =>  '历史、地理',
        'N' =>  '自然科学总论',
        'O' =>  '数理科学和化学',
        'P' =>  '天文学、地球科学',
        'Q' =>  '生物科学',
        'R' =>  '医药、卫生',
        'S' =>  '农业科学',
        'T' =>  '工业技术',
        'U' =>  '交通运输',
        'V' =>  '航空、航天',
        'X' =>  '环境科学、安全科学',
        'Z' =>  '综合性图书',
    ];

    /**
     * 根据中图法分类主分类标识符 获取对应名称
     * @param integer $aid     图书馆aid
     * @return integer  1：汇文系统 2：图创系统
     */
    public static function getCodenName($coden='')
    {
        if (empty($coden)) {
            return self::CODEN_NAME_MAP;
        }
        return empty(self::CODEN_NAME_MAP[$coden]) ? '' : self::CODEN_NAME_MAP[$coden];
    }

}
