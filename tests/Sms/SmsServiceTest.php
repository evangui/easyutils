<?php
/**
 * Created by PhpStorm.
 * User: guiyajun
 * Date: 2018/9/8
 * Time: 23:54
 */
namespace EasyUtils\sms\tests;
use EasyUtils\sms\Service\SmsService;
use PHPUnit\Framework\TestCase;

class SmsServiceTest extends TestCase
{
    public function testSend()
    {
        //发送群发信息短信验证，成功则返回验证码
        $phone_num = '18571593115';
        $sended_code = SmsService::send($phone_num, SmsService::TPL_MASS_SEND, $code_len=4);
        ve($sended_code, 0);
        $this->assertEquals(4, strlen($sended_code));

        //获取最近发送的验证码，与用户输入码验证比较
        $latest_code = SmsService::getLatestCode($phone_num, SmsService::TPL_MASS_SEND);
        ve($latest_code, 0);
        $this->assertEquals($sended_code, $latest_code);
    }

}