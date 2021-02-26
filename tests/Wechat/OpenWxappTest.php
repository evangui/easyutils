<?php
/**
 * Created by PhpStorm.
 * User: guiyajun
 * Date: 2018/9/8
 * Time: 23:54
 */
namespace EasyUtils\message\tests;
use app\weixin\model\LibApp;
use EasyUtils\Kernel\constant\WeixinConst;
use EasyUtils\Kernel\Support\LibConf;
use EasyUtils\Wechat\Service\OpenWxapp;
use PHPUnit\Framework\TestCase;

class OpenWxappTest extends TestCase
{
    public function testGetDrafts()
    {
        $res = OpenWxapp::getInstance(WeixinConst::WXAPP_NAME_CULTURE_TOUR, 6003)->getDrafts();
        ve($res);
        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }

    public function testGetPage()
    {
//        v(LibApp::getByAid(4003, 'culture', ['_cacheTime'=> 0]));

//        $res = OpenWxapp::getInstance(WeixinConst::WXAPP_NAME_CULTURE_TOUR, 6000)->getPage();
        $res = OpenWxapp::getInstance(WeixinConst::WXAPP_NAME_CULTURE, 4003)->getPage();
        ve($res);
        $this->assertEquals(true, count($res['data']) > 0); //使用断言方法 比较结果值
    }



}