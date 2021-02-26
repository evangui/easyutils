<?php
/*
 * 微信ocr功能接口服务类
 *
 * WxOcr.php
 * 2019-08-01 guiyj<guiyj007@gmail.com>
 *
 * 用于ocr功能，目前主要是身份证图片识别
 */
namespace EasyUtils\ocr\Service;

use app\weixin\logic\EasyOcr;
use EasyUtils\Kernel\Support\HandlerFactory;

/**
 * 读者头像绑定相关业务逻辑处理类
 * Class ReaderFace
 * @package app\uar\logic
 */
class WxOcr implements IOcr
{
    /**
     * ID card OCR.
     *
     * @param string $path
     * @param string $type
     *
     * @return array
     *
     * @throws \EasyUtils\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function idCard($card_side, $file_path, $file_size='')
    {
        /**
         * 上传图像保存
         */
        $relative_dir = 'runtime/wxcode/';
        $filename     = md5('YmdH' . $file_path . $file_size).".png";
        list($file_path, $file_url) = file_complete_path($relative_dir . $filename);
        $res = move_uploaded_file($_FILES["photo"]["tmp_name"], $file_path);
        if (!$res) {
            biz_exception('程序错误：图片存储失败');
        }

        /**
         * easy wechat没有实现部署小程序ocr识别的代码，继承其基类，自定义client
         */
        $app = HandlerFactory::easyWechat(HandlerFactory::MINI_PROGRAM, '');
        $app['easyocr'] = function ($app) {
            return new EasyOcr($app);
        };

        $response = $app->easyocr->idCard($file_url, 'photo');
        if (0 != $response['errcode']) {
            if (101001 == $response['errcode']) {
                $card_side_tip = $card_side == 'front' ? '正面' : '背面';
                $response['errmsg'] = '请上传清晰的身份证' . $card_side_tip . '照片';;
            }
            return biz_exception($response['errmsg']);
        }

        unset($response['errcode']);
        unset($response['errmsg']);
        $response['type'] = strtolower($card_side);
        return $response;
    }


}
