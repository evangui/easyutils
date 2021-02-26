### apibase: 所有被封装的业务模块service层，进行接口请求处理时的公用框架封装
- AbstractApiFacade.php 静态&转发请求的门面类抽象定义
- AbstractApiHandler.php api执行类抽象定义
- ApiException.php api异常类
- config.php api调用需要用到的公用配置


### 使用说明
1. 明确封装的接口请求所属模块。如 用户模块:user; 图书模块:book; 图书借还模块: br  

2. 明确封装的接口请求所属接口系统。如 微信图书馆:wxlib; 图书管理系统: libsys； 前置机： forward

3. 根据所属模块与所属接口系统，将代码部署在对应目录下
 - 如 所属模块为用户模块；所属接口系统为微信图书馆，则将代码待部署在 EasyUtils/user/service/libsys/目录下
 - 根据接口功能，新建接口处理类文件。并继承抽象类 \EasyUtils\Apibase\AbstractApiHandler 。实现方法 getApiSysCode （可参考EasyUtils/user/service/libsys/UserCenter.php）

4. 在新建的接口处理类文件中，编写具体接口处理方法。
 - 通常情况下，仅需要发送接口请求即可
 - 可参考EasyUtils/user/service/libsys/UserCenter.php类的getUsers方法
 - 如需要签名验证的接口，则可参考示例 方法：EasyUtils/user/service/wxlib/WxLib.php类文件的signApiDemo方法
 - 接口名尽量用完整含义的名称，注意避免同模块不同文件的接口名冲突

5. 在模块下的门面类文件的类头上，添加具体接口方法的注释，以便IDE可以自动识别。调用接口方法，则用该门面类进行静态调用
 - 如在EasyUtils/user/service/UserFacade类的头注释中，添加行：@method static array getUsers($uids, $cacheOpt = ['_cacheTime'=> 0])  获取微信用户信息（批量根据users的主键ID）
 - 调用方式如：\EasyUtils\User\Service\UserFacade::getUsers()
