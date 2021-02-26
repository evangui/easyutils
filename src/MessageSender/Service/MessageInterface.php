<?php
/**
 * MessageInterface.php
 * 2020-02-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\MessageSender\Service;


interface MessageInterface
{
    /**
     * @param array $data
     * @return bool
     */
    public function send($data);
}