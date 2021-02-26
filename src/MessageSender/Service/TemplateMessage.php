<?php
/**
 * TemplateMessage.php
 * 2020-02-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\MessageSender\Service;


use EasyUtils\Wechat\Service\OpenWe;
use GuzzleHttp\Exception\GuzzleException;

class TemplateMessage implements MessageInterface
{
    public function send($data)
    {
        $result = false;
        try {
            $result = OpenWe::templateMessage($data);
        } catch (\Exception $e) {
            trace('发送模板消息失败: '.$e->getMessage().' '.json_encode($data), 'message');
        } catch (GuzzleException $e) {
            trace('发送模板消息失败: '.$e->getMessage().' '.json_encode($data), 'message');
        }
        return $result;
    }
}