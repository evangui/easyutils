<?php

namespace EasyUtils\Kernel\Support;

class LbsUtil
{
    /**
     * 计算两点之间距离
     * @param float $lng1 用户经度
     * @param float $lat1 用户纬度
     * @param float $lng2 数据库记录经度
     * @param float $lat2 数据库记录纬度
     * @return float|int
     */
    public static function calDistance($lng1,$lat1,$lng2,$lat2){
        return intval(6378.138 * 2 * asin(
                sqrt(
                    cos($lat1 * 3.1415926 / 180) * cos($lat2 * 3.1415926 / 180) * pow(sin(($lng1 * 3.1415926 / 180 - $lng2 * 3.1415926 / 180) / 2), 2)
                    + pow(sin(($lat1 * 3.1415926 / 180 - $lat2 * 3.1415926 / 180) / 2), 2)
                )
            ) * 1000);
    }

    /**
     * 获取数据库查询距离字段
     * @param float $lng 用户经度
     * @param float $lat 用户纬度
     * @param float $longitude_column 数据库记录经度
     * @param float $latitude_column 数据库记录纬度
     * @return float|int
     */
    public static function getDistanceField($lng, $lat, $longitude_column, $latitude_column) {
        $distanceField  = "  ROUND(6378.138 * 2 * ASIN( ";
        $distanceField .= "  SQRT(COS({$lat} * 3.1415926 / 180) * COS({$latitude_column} * 3.1415926 / 180) * ";
        $distanceField .= "  POW(SIN(({$lng} * 3.1415926 / 180 - {$longitude_column} * 3.1415926 / 180) / 2),2) ";
        $distanceField .= "  + POW(SIN(({$lat} * 3.1415926 / 180 - {$latitude_column} * 3.1415926 / 180) / 2),2) ";
        $distanceField .= "  ) ) * 1000) AS distance ";
        return $distanceField;
    }
}
