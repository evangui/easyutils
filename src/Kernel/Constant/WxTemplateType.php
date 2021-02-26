<?php
/*
 * 微信模板消息分类常量类
 *
 * WxTemplateType.php
 * 2019-04-26 guiyj<guiyj007@gmail.com>
 *
 * 被定义的每个常量 与数据库中的模板消息类型id一致
 */
namespace EasyUtils\Kernel\Constant;

class WxTemplateType
{
    // 1 借阅到期通知
    const BORROW_RETURN_OVERDUE = 1;

    // 2 图书馆借书通知
    const BORROW_BOOK = 2;

    // 3 图书馆还书通知
    const RETURN_BOOK = 3;

    // 4 空间预约成功通知
    const SPACE_RESERVE_SUCCESS = 4;

    // 5 空间预约失败通知
    const SPACE_RESERVE_FAIL = 5;

    // 6 Notification
    const NOTIFICATION = 6;

    // 7 学校通知
    const SCHOOL_NOTIFICATION = 7;

    // 8 学术讲座通知
    const SCHOOL_LECTURE = 8;

    // 9 用户反馈问题
    const USER_FEEDBACK = 9;

    // 11 图书预约成功通知
    const BOOK_RESERVE_SUCCESS = 11;

    // 12 图书预约失败通知
    const BOOK_RESERVE_FAIL = 12;

    // 13 图书预约已取书通知
    const BOOK_RESERVE_BOOK_FETCHED = 13;

    // 14 图书预约超期通知
    const BOOK_RESERVE_OVERDUE = 14;

    // 15 图书预借成功通知
    const BOOK_PRELEND_SUCCESS = 15;

    // 16 图书预借失败通知
    const BOOK_PRELEND_FAIL = 16;

    // 17 图书预借已取书通知
    const BOOK_PRELEND_FETCHED = 17;

    // 18 图书预借超期通知
    const BOOK_PRELEND_OVERDUE = 18;

    // 19 活动报名成功通知
    const ACTIVE_SIGNUP_SUCCESS = 19;

    // 20 活动签到前提醒
    const ACTIVE_SIGNIN_REMIND = 20;

    // 21 荐购成功
    const RECOMMEND_BUY_SUCCESS = 21;

    // 22 荐购失败
    const RECOMMEND_BUY_FAIL = 22;

    // 23 图书馆办证成功通知
    const READER_CARD_OPEN_SUCCESS = 23;

    // 24 退款通知
    const REFUND = 24;

    // 25 预约存包柜登记审核成功
    const STORAGE_CABINET_REGISTER_AUDIT_SUCCESS = 25;

    // 26 预约存包柜登记审核失败
    const STORAGE_CABINET_REGISTER_AUDIT_FAIL = 26;

    // 27 申请结果通知
    const APPLY_RESULT = 27;

    // 28 服务到期提醒
    const SERVICE_EXPIRE_REMIND = 28;

    // 29 存包柜预约结果通知
    const STORAGE_CABINET_RESERVE_RESULT = 29;

    // 30 电子阅览室预约结果通知
    const DIGITAL_ROOM_RESERVE_RESULT = 30;

    // 54 推荐结果
    const RECOMMEND_BUY_RESULT = 54;

    // 55 荐购图书已到馆
    const RECOMMEND_BOOK_ARRIVE = 55;

}

