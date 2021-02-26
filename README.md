## 综合工具类封装sdk

### Kernel
项目通用基础类库，含通用配置、助手方法、所有应用通用的基类定义
#### Kernel\helper目录: 
本目录用来存放工具助手函数与静态工具类

- 工具助手函数
    - 如需引入，可使用 load_helper('cache'); 引入本目录下的 cache_helper.php文件
    - 具体使用，可参见demo_common.php，该文件代码建议放在application/common.php中
    - 命名约定，遵循helper_xxx.php 的格式，引入时可通过  load_helper('xxx') 或 load_helper('xxx_helper')
	
- 静态工具类
    - 对于复杂的工具方法，建议封装在静态工具类中（好处：变量空间与对象引用方式更加灵活，命名空间更方便使用）

### Apibase目录: 所有被封装的业务模块service层，进行接口请求处理时的公用框架封装
- AbstractApiFacade.php 静态&转发请求的门面类抽象定义
- AbstractApiHandler.php api执行类抽象定义
- ApiException.php api异常类
 