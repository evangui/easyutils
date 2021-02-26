<?php
/*
 * 百度ocr功能接口服务类
 *
 * BaiduOcr.php
 * 2019-08-01 guiyj<guiyj007@gmail.com>
 *
 * 用于ocr功能，目前主要是身份证图片识别
 */
namespace EasyUtils\ocr\Service;

use EasyUtils\Kernel\Support\HandlerFactory;

require_once env('extend_path') . 'baidu/AipOcr.php';

/**
 * 读者头像绑定相关业务逻辑处理类
 * Class ReaderFace
 * @package app\uar\logic
 */
class BaiduOcr implements IOcr
{
    // 你的 APPID AK SK
    const BD_APP_ID     = 'AppID';
    const BD_API_KEY    = 'AgrbUaUD6yKbCKVC7viygvfA';
    const BD_SECRET_KEY = 'MmXuGW716CuTwYVMxYfNVUEXSm1RX7aO';

    //百度接口错误码说明 https://ai.baidu.com/docs#/Face-ErrorCode-V3/top
    public static $bgCodeMap = [
        '216201' => '图片格式错误',
    ];

    public static $client;                  //百度人脸库sdk client

    /**
     * 获取百度人脸库sdk client
     * @return \AipOcr
     */
    public static function getClient()
    {
        if (empty(self::$client)) {
            self::$client = new \AipOcr(self::BD_APP_ID, self::BD_API_KEY, self::BD_SECRET_KEY);
        }
        return self::$client;
    }

    /**
     * 通用文字识别接口
     *
     * @param string $image - 图像数据，base64编码，要求base64编码后大小不超过4M，最短边至少15px，最长边最大4096px,支持jpg/png/bmp格式
     * @param array $options - 可选参数对象，key: value都为string类型
     * description options列表:
     *   language_type 识别语言类型，默认为CHN_ENG。可选值包括：<br>- CHN_ENG：中英文混合；<br>- ENG：英文；<br>- POR：葡萄牙语；<br>- FRE：法语；<br>- GER：德语；<br>- ITA：意大利语；<br>- SPA：西班牙语；<br>- RUS：俄语；<br>- JAP：日语；<br>- KOR：韩语；
     *   detect_direction 是否检测图像朝向，默认不检测，即：false。朝向是指输入图像是正常方向、逆时针旋转90/180/270度。可选值包括:<br>- true：检测朝向；<br>- false：不检测朝向。
     *   detect_language 是否检测语言，默认不检测。当前支持（中文、英语、日语、韩语）
     *   probability 是否返回识别结果中每一行的置信度
     * @return array
     */
    public function general($file_data, $options=array())
    {
        $client = self::getClient();
        //可选参数
        $options = [
            'detect_risk' => 'true',
        ];

        // 带参数调用身份证识别
        $result = $client->basicGeneral($file_data, $options);
//        $err_msg = self::getErrMsg($result['image_status'], $result['risk_type'], $card_side);
//        if ($err_msg) {
//            biz_exception($err_msg);
//        }

        //百度本天调用次数累加
//        self::dayCallTimes(true);
        $arr = $result['words_result'];
        return array_column($arr, 'words');
    }

    /**
     * ID card OCR.
     *
     * @param string $card_side //front：身份证含照片的一面；back：身份证带国徽的一面
     * @param string $file_path
     *
     * @return array
     */
    public function idCard($card_side, $file_path, $file_size='')
    {
        $client = self::getClient();
        $idCardSide = $card_side; //front - 身份证含照片的一面 ，back - 身份证带国徽的一面
        //可选参数
        $options = [
            'detect_risk' => 'true',
        ];

        // 带参数调用身份证识别
        $result = $client->idcard(file_get_contents($file_path), $idCardSide, $options);
        trace($result, 'idcard_res');
        if (empty($result['image_status'])) {
            $result['image_status'] = $result['risk_type'] = '';
        }
        $err_msg = self::getErrMsg($result['image_status'], $result['risk_type'], $card_side);
        if ($err_msg) {
            biz_exception($err_msg);
        }

        //获取正常识别的身份证信息
        if ('front' == $card_side) {     //front：身份证含照片的一面；back：身份证带国徽的一面
            $ret = [
                'type' => 'front',
                'name' => $result['words_result']['姓名']['words'],
                'id'   => $result['words_result']['公民身份号码']['words'],
                'addr' => $result['words_result']['住址']['words'],
                'sex'  => $result['words_result']['性别']['words'],
            ];
        } else {
            $ret = [
                'type' => 'back', //front：身份证含照片的一面；back：身份证带国徽的一面
                'valid_date' => "{$result['words_result']['签发日期']['words']}-{$result['words_result']['失效日期']['words']}",
            ];
        }

        //百度本天调用次数累加
        self::dayCallTimes(true);

        return $ret;
    }

    /**
     * 百度ocr接口调用次数累加 与 获取
     * @param bool $incr
     * @return bool|string
     */
    public static function dayCallTimes($incr = false)
    {
        $redis = HandlerFactory::redis();
        $redis_key = 'ocr_day_times_' . date('Ymd');
        if ($incr) {
            $res = $redis->incr($redis_key);
            $redis->expireAt($redis_key, time()+86400);
        } else {
            return $redis->get($redis_key);
        }
    }

    /**
     * 根据识别的图片结果和风险监测结果，返回中文提示
     * @param $image_status
     * @param $risk_type
     * @return string
     */
    private static function getErrMsg($image_status, $risk_type, $card_side='') {
        $card_side_tip = $card_side == 'front' ? '正面' : '背面';

        //非正常结果
        switch ($image_status){
            case 'normal':
                return '';
                break;
//            case 'reversed_side':
//                return '身份证不匹配或未摆正';
//                break;
//            case 'non_idcard':
//                return '上传的图片中不包含身份证';
//                break;
//            case 'blurred':
//                return '身份证模糊';
//                break;
//            case 'over_exposure':
//                return '身份证关键字段反光或过曝';
//                break;
            default:
                if (in_array($risk_type, ['copy', 'temporary', 'screen'])) {
                    return '身份证不能为翻拍件、复印件、临时身份证';
                } else {
                    return '请上传清晰的身份证' . $card_side_tip . '照片';
                }
                break;
        }
        return '';
    }

    private static function getCodeTip($code, $original_msg)
    {
        return isset(self::$bgCodeMap[$code]) ? self::$bgCodeMap[$code] : $original_msg;
    }

}
