<?php
/**
 * MessageFactory.php
 * 2020-02-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\MessageSender\Service;


class MessageFactory
{
    /**
     * 根据渠道类型，获取实际的消息处理对象
     * @param integer $send_type
     * @return MessageInterface
     */
    public static function getRelay($send_type)
    {
        switch ($send_type) {
            case 1 :
                $obj = new SubscribeMessage();
                break;
            case 2 :
                $obj = new TemplateMessage();
                break;
            default :
                $obj = new SubscribeMessage();
        }
        return $obj;
    }
}