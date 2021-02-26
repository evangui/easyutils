<?php
/**
 * SubscribeMessage.php
 * 2020-02-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\MessageSender\Service;


use EasyUtils\Wechat\Service\Wxapp;
use GuzzleHttp\Exception\GuzzleException;

class SubscribeMessage implements MessageInterface
{
    public function send($data)
    {
        $result = false;
        try {
            $result = Wxapp::getInstance($data['wxapp_name'])->subscribeMessage($data);
        } catch (\Exception $e) {
            trace('发送订阅消息失败: '.$e->getMessage().' '.json_encode($data), 'message');
        } catch (GuzzleException $e) {
            trace('发送订阅消息失败: '.$e->getMessage().' '.json_encode($data), 'message');
        }
        return $result;
    }
}