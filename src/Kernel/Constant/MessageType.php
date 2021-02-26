<?php
/**
 * MessageType.php
 * 2020-02-11  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\Kernel\Constant;


class MessageType
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

    // 10 取消反占座提醒
    const CANCEL_SEAT_REMIND = 10;

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

    // 31 空间预约使用提醒
    const SPACE_USE_START_REMIND = 31;

    // 32 空间预约结束提醒
    const SPACE_USE_END_REMIND = 32;

    // 33 空间预约失效提醒
    const SPACE_EFFECTIVE_RESULT = 33;

    // 34 反占座提醒
    const SEAT_REMIND = 34;

    // 35 座位状态异常提醒
    const SEAT_ABNORMAL_REMIND = 35;

    // 36 反占座申诉受理提醒
    const SEAT_APPEAL_REMIND = 36;

    // 37 领读作品审核提醒
    const READING_AUDIT_REMIND = 37;

    // 38 朗读亭作品上传成功通知
    const BOKUN_WORKS_REMIND = 38;

    //39 签到结果提醒
    const SIGN_RESULT_REMIND = 39;

    // 40 活动取消通知
    const ACTIVE_SIGNUP_CANCEL = 40;

    // 41 活动开始提醒
    const ACTIVE_START_REMIND = 41;

    // 42 社团申请结果通知
    const APPLY_COMMUNITY_RESULT = 42;

    // 43 点单配送提醒
    const SERVE_DELIVERY_REMIND = 43;

    // 44 预约到馆使用提醒
    const LIBRARY_USE_START_REMIND = 44;

    // 45 预约到馆结束提醒
    const LIBRARY_USE_END_REMIND = 45;

    //46 图书借阅成功通知
    const BOOK_BORROW_SUCCESS = 46;

    //47 借阅取消通知
    const BOOK_BORROW_CANCEL = 47;

    //48 座位预约成功通知
    const RESERVE_SEAT_SUCCESS = 48;

    //49 座位预约提醒
    const RESERVE_SEAT_REMIND = 49;

    //50 座位预约签离提醒
    const RESERVE_SEAT_SIGNOUT_REMIND = 50;

    //51 座位预约暂离提醒
    const RESERVE_SEAT_LEAVE_REMIND = 51;

    //52 座位预约反占座提醒
    const RESERVE_SEAT_GRAB_REMIND = 52;

    // 54 推荐结果
    const RECOMMEND_BUY_RESULT = 54;

    // 55 荐购图书已到馆
    const RECOMMEND_BOOK_ARRIVE = 55;
}