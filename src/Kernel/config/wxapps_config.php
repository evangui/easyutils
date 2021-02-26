<?php
//微信小程序配置文件
use EasyUtils\Kernel\constant\WxTemplateType;
return [
    /**
     * 小程序关键配置参数
     */
    'wxapps' => [
        //布狗微信图书馆小程序
        'wxlib' => [
            'appid'  => 'wx6381563fac6c91e6',
            'secret' => 'b72b5a879087ee9a225f5246a0a712f6',
        ],
        //小程序名：布狗阅读
        'read' => [
            'appid'  => 'wx4a981ae58c72f9c4',
            'secret' => 'afbbaf9e082c3a6beb1508f169506c96',
        ],

        //测试号小程序
        'bookgo_test' => [
            'appid'  => 'wx4a981ae58c72f9c4',
            'secret' => 'afbbaf9e082c3a6beb1508f169506c96',
        ],

        /**
         * 小程序模板消息设置
         */
        'templates' => [
            //布狗微信图书馆小程序的模板消息
            'wxlib' => [
                //活动报名成功通知
                WxTemplateType::ACTIVE_SIGNUP_SUCCESS => [ '9aekVIzWlIkfCxRSkKbEIU4maZRExnY88oYY-waiKTg', 'pages/index/index'],
                //图书馆办证成功通知
                WxTemplateType::READER_CARD_OPEN_SUCCESS => [ 'pmWt5cZgsp_FimpxcpGhEc30OoTvxk2ht54tajVY9WU', ''],
                //退款通知
                WxTemplateType::REFUND => [ 'NjXyzO-VmgOos-g0OUsFCbojO4vNo5vfSVXaWXE7Kt8', ''],
            ],
            //布狗布狗阅读小程序的模板消息
            'read' => [

            ],

        ],
    ]

];