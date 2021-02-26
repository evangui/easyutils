## 前置机代理服务SDK

/*
 * 前置机封装SDK测试示例文件
 *
 * ForwardTest.php
 * 2019-01-30 11:09  guiyj<guiyj007@gmail.com>
 *
 */
 ```
namespace EasyUtils\CustomBiz\Forward\Example;
use EasyUtils\CustomBiz\Forward\Service\ForwardProxyFactory;

$book_proxy = ForwardProxyFactory::getBookProxy(12);
$res = $book_proxy->getPositionByBarcodes('000649890,000649891');
var_dump($res);
```