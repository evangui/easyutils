## 自定义业务共享SDK总目录
~~~
├─common			                项目通用基础类库目录
│  ├─config                      所有项目的公用程序配置目录
│  │  ├─api_config.php          接口调用的参数配置
│  ├─constant                   常量类定义目录
│  │  ├─AbstractCodeConst.php   常量码定义抽象类 
│  │  ├─AbstractMapConst.php    字典常量抽象类 
│  │  ├─ApiCodeConst.php        接口返回状态码常量类 
│  │  └─SysNameConst.php        博客业务系统名称码常量类 
│  ├─controller                  公用控制器目录
│  │  └─BaseController.php      全体项目公用控制器 
│  ├─exception                   公用异常类目录
│  │  └─BaseController.php      业务通用异常类文件
│  ├─helper                     工具助手函数与静态工具类目录
│  ├─traits                     公用trait定义目录
│  └─view                       博库公用模板目录（如404,500输出,消息输出公用模板）
│
├─apibase			               博库业务系统接口调用基础sdk
│  ├─ReqData.php                请求数据类
│  ├─ApiException.php           api异常类
│  ├─AbstractApiFacade.php      静态&转发请求的门面类抽象定义
│  └─AbstractApiHandler.php     api执行类抽象定义
│
├─user			               用户与读者信息业务封装SDK
├─book			               图书业务封装SDK
├─br			                   图书借还流通业务封装SDK
├─active			               活动业务封装SDK
└─forward			               前置机代理服务SDK
~~~


### common
项目通用基础类库，含通用配置、助手方法、所有应用通用的基类定义
#### common\helper目录: 
本目录用来存放工具助手函数与静态工具类

- 工具助手函数
    - 如需引入，可使用 load_helper('cache'); 引入本目录下的 cache_helper.php文件
    - 具体使用，可参见demo_common.php，该文件代码建议放在application/common.php中
    - 命名约定，遵循helper_xxx.php 的格式，引入时可通过  load_helper('xxx') 或 load_helper('xxx_helper')
	
- 静态工具类
    - 对于复杂的工具方法，建议封装在静态工具类中（好处：变量空间与对象引用方式更加灵活，命名空间更方便使用）

### apibase目录: 所有被封装的业务模块service层，进行接口请求处理时的公用框架封装
- AbstractApiFacade.php 静态&转发请求的门面类抽象定义
- AbstractApiHandler.php api执行类抽象定义
- ApiException.php api异常类

### SDK-For-Each-Module
~~~
├─service			封装共享sdk接口服务层调用目录
├─logic			封装共享sdk业务逻辑层调用
├─model			service或logic依赖的model层。方式完全同在应用中一样，更改命名空间即可。【注】：非特殊情况，不建议将model层放入SDK包中
├─example			所有sdk的调用示例文件夹
~~~
#### 注：model层，请不要随意添加。适用添加model文件的情况，须满足条件：
1. 业务非通用型底层业务（如获取全站配置信息就是通用的底层业务）
2. 在写模块SDK时，需要紧密依赖model提供的功能，暂不适合用接口给出的；如方便通过接口给出功能的，则用接口提供功能，而非model方法。

### 使用说明
1. 明确封装的接口请求所属模块。如 用户模块:user; 图书模块:book; 图书借还模块: br  

2. 明确封装的接口请求所属接口系统。如 微信图书馆:wxlib; 微信图书馆v2.0:wxlib_v2;图书管理系统: libsys； 前置机： forward

3. 根据所属模块与所属接口系统，将代码部署在对应目录下
 - 如 所属模块为用户模块；所属接口系统为微信图书馆，则将代码待部署在 EasyUtils/user/service/libsys/目录下
 - 根据接口功能，新建接口处理类文件。并继承抽象类 \EasyUtils\Apibase\AbstractApiHandler 。实现方法 getApiSysCode （可参考EasyUtils/user/service/libsys/UserCenter.php）

4. 在新建的接口处理类文件中，编写具体接口处理方法。
 - 通常情况下，仅需要发送接口请求即可
 - 可参考EasyUtils/active/service/wxlib_v2/UserActive.php类的pagelistUserActive方法
 - 如需要签名验证的接口，则可参考示例 方法：EasyUtils/user/service/wxlib/WxLib.php类文件的signApiDemo方法
 - 接口名尽量用完整含义的名称，注意避免同模块不同文件的接口名冲突

5. 在模块下的门面类文件的类头上，添加具体接口方法的注释，以便IDE可以自动识别。调用接口方法，则用该门面类进行静态调用
 - 如在EasyUtils/user/service/UserFacade类的头注释中，添加行：@method static array getUsers($uids, $cacheOpt = ['_cacheTime'=> 0])  获取微信用户信息（批量根据users的主键ID）
 - 调用方式如：\EasyUtils\user\Service\UserFacade::getUsers()，也可直接通过实例化指定api处理类进行调用（【不建议】），如：(new UserCenter())->getUsers($uids, 0)
 
6. 接口调用请求的参数与返回参数，会在通用日志文件中记录。
 - 当接口返回code不为0时，则统一会抛出ApiException异常(继承自BizException)，请进行拦截该异常
 - 如果要获取对应接口原始返回的code与msg, 可通过拦截异常对象的getCode与getMessage方法获取
 - 当异常拦截后，可通过异常对象的 getData方法，获取详细异常调用信息
   > 如：dump($exception->getData('data')) 
   
   > 或通过 $exception->getApiOutput() 获取原接口输出的原始字符串内容
   
7. 对于接口请求，如长期不变的数据，尽量使用缓存。
 - 可通过统一方法cache_method进行文件缓存。
 > 如需要用redis缓存，可在cacheOpt参数中指定 _cacheHandler为redis
 > 默认当数据获取不到，或内容为空时，不缓存内容，可以通过cacheOpt参数中的 _ignoreEmpty=true缓存空内容。
 
8. 当接口请求返回的data为空时，可通过expectDataList()方法，将空字符串转化为空数组array()

9. 可通过setErrorRetryTimes方法指定当接口未正确响应时，进行重试的次数，默认不进行错误重试。
   或通过request方法最后一个参数指定重试次数。request($uri, $param, $timeout=10, $try_times=1) 