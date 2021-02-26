<?php
/*
 * 流通日志类型码常量类
 *
 * SysNameConst.php
 * 2019年2月25日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Kernel\constant;

class CirlogTypeConst extends AbstractCodeConst
{
    /**
     * 日志类型，通图创的日志类型
     */
    const LOG_TYPE_BORROW_BOOK = '30001';
    const LOG_TYPE_RETURN_BOOK = '30002';
    const LOG_TYPE_RENEW_BOOK  = '30003';


}
