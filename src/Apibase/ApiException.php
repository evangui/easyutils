<?php
namespace EasyUtils\apibase;

use EasyUtils\Kernel\exception\BizException;

class ApiException extends BizException
{
	public function errorMessage() 
	{
		return $this->getMessage();
	}
}
