<?php
namespace EasyUtils\CustomBiz\Forward;

/**
 * 前置机接口代理异常类
 */
class ForwordProxyException extends \Exception 
{
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
