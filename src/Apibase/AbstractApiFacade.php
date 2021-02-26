<?php
/*
 * 静态&转发请求的门面类抽象定义
 *
 * AbstractApiFacade.php
 * 2019年1月28日 下午16:27:06  guiyj<guiyj007@gmail.com>
 *
 * 用于所有模块接口的装饰（含下级执行类请求转发；静态调用）
 * 该类封装了api装饰类的核心方法
 */
namespace EasyUtils\apibase;

use think\Exception;
use think\facade\Config;

/**
 * 静态&转发请求的门面类
 */
abstract class AbstractApiFacade
{
    /**
     * 接口系统标致: 微信图书馆。目前需要和模块下的目录名保持一致
     * @var string
     */
    const API_SYS_WXLIB = 'wxlib';

    /**
     * 接口系统标致: 微信图书馆。目前需要和模块下的目录名保持一致
     * @var string
     */
    const API_SYS_WXLIB_V2 = 'wxlib_v2';
    
    /**
     * 接口系统标致: 图书馆系统。目前需要和模块下的目录名保持一致
     * @var string
     */
    const API_SYS_LIBSYS = 'libsys';


    /**
     * 接口系统标致: 所有前置机系统。目前需要和模块下的目录名保持一致
     * @var string
     */
    const API_SYS_FORWARD = 'forward';
    
    const API_SYS_DB = 'db';
    
    /**
     * 方法-处理实例 的容器
     * @var array
     */
    public static $handlers = [];
    
    public static $cachedHandersPath = [];
    
    /**
     * 是否加载过本包的配置文件
     * @var string
     */
    public static $loadConfig = false;
    
    /**
     * 接口请求系统名
     * @var string
     */
    public static $apiSysCode;
    
    /**
     * 最终处理接口请求的类名
     * @var string
     */
    public static $finalHandlerClass;
    
    public static $moduleName;
	
    /**
     * @var array
     */
    public static $exceptMethods = [
        'getApiSysCode',
        '__construct',
        'getNonceStr',
        'request',
        'getMillisecond',
        'where',
        'setSign',
        'getInput',
        'isKeySet',
    ];
    
    /**
     * 加载统一框架的接口配置文件
     */
    public static function loadConfig() 
    {
        if (!self::$loadConfig) {
            if (is_think5_1()) {
                Config::load(__DIR__ . '/../common/config/api_config.php');
            } else {
                \think\Config::load(__DIR__ . '/../common/config/api_config.php');
            }
            self::$loadConfig = true;
        }
    }
    
    /**
     * 根据继承子类，获取并设置 子类模块名
     * @return string
     */
    public static function getModuleNameBySubclass() 
    {
        $sub_class_names = explode('\\', get_called_class());
        $sub_class_name = str_replace('Facade', '', $sub_class_names[count($sub_class_names) -1]);
        self::$moduleName = strtolower($sub_class_name);
        return self::$moduleName;
    }
    
    
    /**
     * 获取&初始化接口请求处理句柄（从容器中根据方法名获取）
     * @param string $api_sys_code
     * @return 
     */
    public static function getHandler($method) 
    {
        if (isset(self::$handlers[$method])) {
            return self::$handlers[$method];
        }
        
        self::loadConfig();
        self::getModuleNameBySubclass();
        
        self::$cachedHandersPath = self::cacheSubMethods();
        //从缓存的方法名与文件名中取得
        if (!isset(self::$cachedHandersPath[$method])) {
            self::$cachedHandersPath = [];
            self::$cachedHandersPath = self::cacheSubMethods(0);
            if (!isset(self::$cachedHandersPath[$method])) {
                throw new \Exception("请调用 cacheSubMethods方法，缓存接口名映射");
            }
        }
        
        list(self::$apiSysCode, self::$moduleName, self::$finalHandlerClass) = explode('__', self::$cachedHandersPath[$method]);
        self::$handlers[$method] = self::getHandlerByNames(self::$apiSysCode, self::$moduleName, self::$finalHandlerClass);
        return self::$handlers[$method];
    }
     
    /**
     * 通过指定的 接口系统名 与 类文件名，获取接口处理句柄
     * 
     * @param string $api_sys_code  接口系统名
     * @param string $class_name 接口处理类的类名
     * @return mixed
     */
    public static function getHandlerByNames($api_sys_code, $module_name, $class_name) 
    {
        $class_name = str_replace('.php', '', $class_name);
        $container_key = "{$api_sys_code}||{$class_name}";
        
        if (isset(self::$handlers[$container_key])) {
            return self::$handlers[$container_key];
        }
        
        $complete_class = "\EasyUtils\\{$module_name}\Service\\{$api_sys_code}\\{$class_name}";
//         $class_path  = (new \ReflectionClass($complete_class))->getFileName();
//         self::$handlers[$container_key] = new $complete_class();

        try {
            self::$handlers[$container_key] = (new \ReflectionClass($complete_class))->newInstance();
        } catch (\ReflectionException $e) {
            //报类不存在错误时，重新刷新缓存，本次任然报错退出
            if (str_exist($e->getMessage(), 'does not exist')) {
                self::cacheSubMethods(0);
                biz_exception($e->getMessage() . "【已重新加载方法名映射，请重试】");
            } else {
                throw $e;
            }
        }

        return self::$handlers[$container_key];
    }
    
    /**
     * 缓存方法名与文件名的映射
     */
    protected static function cacheSubMethods($cacheTime=8640000) 
    {
        $cacheKey = 'cache_methods_map:' . self::$moduleName;
        if ($cacheTime > 0) {
            $res = cache($cacheKey);
            if (!empty($res)) {
                try {
                    self::$cachedHandersPath = unserialize($res);
                    return self::$cachedHandersPath;
                } catch (\Exception $e) {
                }
            }
        }

        self::$cachedHandersPath = [];
        $extend_path = is_think5_1() ? env('extend_path') : EXTEND_PATH;
        $module_path = $extend_path . 'EasyUtils' . DIRECTORY_SEPARATOR . self::$moduleName;
        $ds = DIRECTORY_SEPARATOR;
        
        //扫描该目录下的所有api处理文件
        $dirs = [
            $module_path . $ds . 'service' . $ds. self::API_SYS_WXLIB,
            $module_path . $ds . 'service' . $ds. self::API_SYS_WXLIB_V2,
            $module_path . $ds . 'service' . $ds. self::API_SYS_LIBSYS,
            $module_path . $ds . 'service' . $ds. self::API_SYS_FORWARD,
            $module_path . $ds . 'service' . $ds. self::API_SYS_DB,
        ];
        foreach ($dirs as $dir) {
            if(is_dir($dir)) {
                $file_list = scandir($dir);
                unset($file_list[0], $file_list[1]);
                //逐个打开文件，分析需要缓存的方法映射
                foreach ($file_list as $filename) {
                    self::setMethodMapByFile($dir . $ds. $filename);
                }
            }
            
        }
        
        cache($cacheKey, serialize(self::$cachedHandersPath), $cacheTime);
        return self::$cachedHandersPath;
//         v(self::$cachedHandersPath);
//         self::$cachedHandersPath = [
//             'getUsers' => 'libsys__user__UserCenter',
//         ];
    }
    
    /**
     * 根据接口文件物理路径，映射方法名与接口调用信息
     * @param string $filepath
     */
    protected static function setMethodMapByFile($filepath) 
    {
        $namespace_path = substr($filepath, strpos($filepath, 'EasyUtils'));
        $namespace_path = '\\' . str_replace(["/", '.php'], ["\\", ''], $namespace_path);
        $_class_names = explode('\\', $namespace_path);
        $class_name = $_class_names[count($_class_names) - 1];
        //获取该子类文件的所有公用方法名
        $methods  = (new \ReflectionClass($namespace_path))->getMethods(\ReflectionMethod::IS_PUBLIC);
        //从公用方法中提取接口映射信息
        foreach ($methods as $v) {
            $dirs = explode('\\', $v->class);
            
            //父类公用方法跳过
            if (in_array($v->name, self::$exceptMethods) || 'EasyUtils\Apibase\AbstractApiHandler' == $v->class || !isset($dirs[3]) ) {
                continue;
            }
            
            $map_path = $dirs[3] . '__' . $dirs[1] . '__' . $class_name;
            self::setHandlerPath($v->name, $map_path);
        }
    
    }
    
    /**
     * 记录方法名与方法执行句柄信息路径的映射关系
     * @param string $method_name
     * @param string $map_path
     * @throws \Exception
     */
    public static function setHandlerPath($method_name, $map_path) 
    {
        if (isset(self::$cachedHandersPath[$method_name])) {
            $msg = "{$method_name} 方法名冲突：" . $map_path . ' vs ' . self::$cachedHandersPath[$method_name];
            $msg .= "\r\n建议采用完整的方法名(如getUserinfoByUid)，或带类标志前缀(如uc_getUserinfo)";
            throw new \Exception($msg);
        }
        self::$cachedHandersPath[$method_name] = $map_path;
    }
    
    /**
     * 自动生成模块facade类的接口注释。方便IDE调用
     */
    public static function createApiAnnotation() 
    {
        self::$moduleName = self::getModuleNameBySubclass();
        $module_path = env('extend_path') . 'EasyUtils' . DIRECTORY_SEPARATOR . self::$moduleName;
        $ds = DIRECTORY_SEPARATOR;
        //扫描该目录下的所有api处理文件
        $dirs = [
            $module_path . $ds . 'service' . $ds. self::API_SYS_WXLIB,
            $module_path . $ds . 'service' . $ds. self::API_SYS_WXLIB_V2,
            $module_path . $ds . 'service' . $ds. self::API_SYS_LIBSYS,
            $module_path . $ds . 'service' . $ds. self::API_SYS_FORWARD,
            $module_path . $ds . 'service' . $ds. self::API_SYS_DB,
        ];
        foreach ($dirs as $dir) {
            if(is_dir($dir)) {
                $file_list = scandir($dir);
                unset($file_list[0], $file_list[1]);
                
                //逐个打开文件，分析需要缓存的方法映射
                foreach ($file_list as $filename) {
                    self::annotateByFile($dir . $ds. $filename);
                }
            }
        }
    }
    
    /**
     * 提取指定类文件的所有方法，生成方法描述信息
     * @param string $filepath
     */
    protected static function annotateByFile($filepath) 
    {
        $namespace_path = substr($filepath, strpos($filepath, 'EasyUtils'));
        $namespace_path = '\\' . str_replace(["/", '.php'], ["\\", ''], $namespace_path);
        $_class_names = explode('\\', $namespace_path);
        $class_name = $_class_names[count($_class_names) - 1];
        
        $methods  = (new \ReflectionClass($namespace_path))->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $v) {
            $dirs = explode('\\', $v->class);
            //父类公用方法跳过
            if (in_array($v->name, self::$exceptMethods) || 'EasyUtils\Apibase\AbstractApiHandler' == $v->class || !isset($dirs[3]) ) {
                continue;
            }
            
            //获取方法参数
            $parameters = $v->getParameters();
            $parameters = array_map(function($param) {
                return '$' . $param->name;
            }, $parameters);
            $params_str = implode(',', $parameters);
            
            //获取方法的注释
            $doc = $v->getDocComment();
            $doc = explode("\n", $doc);
            $comment = trim(str_replace('*', '', $doc[1]));
            
            // eg: @method static array getUsers($uids, $cacheOpt = ['_cacheTime'=> 0])  获取用户信息
            $final_comment = '@method static ' . $v->name . "({$params_str}) {$comment}";
        }
    }
    
    /**
     * 魔术方法静态调用 实际接口处理对象的方法
     */
    public static function __callStatic($method, $params) 
    {
        $handler = static::getHandler($method);
        //如果$params是ReqData对象，则通过ReqData对象初始化接口处理句柄的输入对象参数
        if (isset($params[0]) && $params[0] instanceof ReqData) {
            $handler->setReqObj($params[0]);
        }
        
        return call_user_func_array([$handler, $method], $params);
    }
    
}

