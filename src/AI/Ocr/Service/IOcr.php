<?php
/**
 * 读者头像绑定相关业务逻辑服务封装
 */
namespace EasyUtils\ocr\Service;

require_once env('extend_path') . 'baidu/AipOcr.php';

/**
 * 读者头像绑定相关业务逻辑处理类
 * Class ReaderFace
 * @package app\uar\logic
 */
interface IOcr
{
    /**
     * ID card OCR.
     *
     * @param string $card_side //front：身份证含照片的一面；back：身份证带国徽的一面
     * @param string $file_path
     *
     * @return array
     */
    public function idCard($card_side, $file_path, $file_size='');

}
