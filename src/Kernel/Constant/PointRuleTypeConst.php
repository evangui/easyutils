<?php
/*
 * 积分增减项类型常量
 *
 * PointRuleTypeConst.php
 * 2022-5-2  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Kernel\Constant;

class PointRuleTypeConst extends AbstractCodeConst
{
    public static $pointModuleList = [
        ['code' => 'login', 'name' => '每日登陆'],
        ['code' => 'activity', 'name' => '预约活动'],
        ['code' => 'certification', 'name' => '实名认证'],
        ['code' => 'booking_space', 'name' => '预约到馆'],
        ['code' => 'content_share', 'name' => '内容分享'],
        ['code' => 'volunteer_apply', 'name' => '申请志愿者'],
        ['code' => 'volunteer_join_team', 'name' => '加入志愿者团队'],
        ['code' => 'volunteer_activity', 'name' => '报名志愿者活动'],
        ['code' => 'train', 'name' => '参与培训'],
        ['code' => 'club', 'name' => '加入社团'],
        ['code' => 'cultural_order', 'name' => '文化点单'],
    ];

    const LOGIN_SUCCESS = 1;    //登陆-登陆成功

    const ACTIVITY_SIGNIN = 2;  //预约活动-签到
    const ACTIVITY_NOT_SIGNIN = 3;  //预约活动-未签到

    const CERTIFICATION_SUCCESS = 4;  //实名认证-认证成功

    const BOOKING_SPACE_SIGNIN = 5;  //预约场地/空间-签到
    const BOOKING_SPACE_NOT_SIGNIN = 6;  //预约场地/空间-未签到

    const CONTENT_SHARE_SUCCESS = 7;  //内容分享-分享成功

    const VOLUNTEER_APPLY_SUCCESS = 8;  //申请志愿者 申请成功
    const VOLUNTEER_JOIN_TEAM_SUCCESS = 9;  //志愿者-加入团队成功
    const VOLUNTEER_ACTIVITY_SIGNIN = 10;  //志愿者-志愿者活动 - 签到
    const VOLUNTEER_ACTIVITY_NOT_SIGNIN = 11;  //志愿者-报名志愿者活动 - 未签到

    const TRAIN_COMPLETE = 12;  //培训-完成培训

    const CLUB_JOIN_SUCCESS = 13;  //社团-加入成功
    const CULTURAL_ORDER_JOIN = 14;  //文化点单-参与点单

    public static function listPointModules()
    {
        return array_column(self::$pointModuleList, null, 'code');
    }

}
