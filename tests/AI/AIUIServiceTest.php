<?php
/**
 * Created by PhpStorm.
 * User: guiyajun
 * Date: 2018/9/8
 * Time: 23:54
 */
namespace EasyUtils\ai\tests;
use EasyUtils\ai\Service\AIUIService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AIUIServiceTest extends TestCase
{
    public function testAIUI()
    {
        $text_file = 'C:\\Users\\my\\Desktop\\text.md';    //音频文件路径
//        $audio_file = 'C:\\Users\\my\\Desktop\\16kVoice.pcm';    //音频文件路径
        $audio_file = 'C:\\Users\\my\\Desktop\\test.mp3';    //音频文件路径
//        $audio_file = 'C:\\Users\\my\\Desktop\\test.wav';    //音频文件路径
//        $res = (new AIUIService())->voice2text($text_file, 'text');
        $res = (new AIUIService())->voice2text($audio_file, 'mp3');
        ve($res);
    }

    public function testCache()
    {
        $data = 'fdsflk';
        $k = 'aaa';
//        $res = cache([$k => $data], 500);
        $res = cache($k);
        ve($res);
    }


}