<?php
// 工具函数 - 缓存相关
/**
 * 获取redis数据库对象实例
 * @param array $options
 * @return \Redis
 */
function redis_handler($options = [], $conf_key='')
{
    return \EasyUtils\Kernel\Support\HandlerFactory::redis($options, $conf_key);
}

/**
 * 针对方法调用的 存/取 缓存数据  的 通用方法
 *
 * (1)支持针对控制器action输出的缓存
 * (2)支持针对普通方法的返回值缓存
 * (3)支持针对函数与静态方法的返回值缓存
 * (4)可识别忽略空数据的范围，即当选定结果范围为空时，则跳过缓存，直接返回结果(通过$cacheOpt参数控制，请见后面示例)
 * (5)可识别异常模式：优先走实时方法调用，当发生指定异常时，再改用缓存数据请求(通过$cacheOpt参数控制，请见后面示例)
 *
 * @example
 *  // (1)支持针对控制器action输出的缓存。即自定义指定缓存key(一般根据get,post参数，或自定义）
 *   if (empty($_GET['skip_cache'])) {
 *       return cache_method($this, __METHOD__, func_get_args(), ['_cacheTime'=> 600], $this->request->param());
 *       //or
 *       return cache_method($this, __METHOD__, func_get_args(), ['_cacheTime'=> 600], [], true);
 *   }
 *   // (2)支持针对普通方法的返回值缓存
 *   if (empty($_GET['skip_cache'])) {
 *      return cache_method($this, __METHOD__, func_get_args(), ['_cacheTime'=> 600]);
 *   }
 *   //如果被缓存方法，期望接收被传入过期时间参数（$cacheOpt = ['_cacheTime'=> 300]），调用如下：
 *   if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
 *      return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
 *   }
 *  // (3)支持针对函数与静态方法的返回值缓存
 *  if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
 *      return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
 *  }
 *  // (4)可识别忽略空数据的范围，即当选定结果范围为空时，则跳过缓存，直接返回结果
 *  $cacheOpt = ['_cacheTime'=> 300, '_ignoreEmptyKey' => 'username', '_ignoreEmpty' => true]
 *  return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
 *
 *  // (5)可识别异常模式：优先走实时方法调用，当发生指定异常(默认为所有异常抛出)时，再改用缓存数据请求
 *  $cacheOpt = ['_cacheTime'=> 300, '_cache4errStep' => 1]
 *  return cache_method($this, __METHOD__, func_get_args(), $cacheOpt);
 *
 * @author: guiyj007
 *
 * @param object $obj           - 需要缓存方法数据的对象实例，通常在方法中传 $this。如果是缓存函数或静态类方法，则该参数需设置为null或空
 * @param string $methodName    - 缓存不存在时调用方法名
 * @param array $args           - 需要缓存数据的方法参数，通常用 func_get_args() 获取
 * @param array $cacheOpt       - cache控制信息
 *                                  （1）支持时间控制(缓存时长，单位为秒) 如 ['_cacheTime'=> 300]
 *                                   当_cacheTime设置为<=0时，不启用缓存；
 *                                   如果设置为1，则利用当前会话静态变量缓存；
 *                                   其他>0，则利用redis缓存
 *                                  （2）支持结果集为空是否缓存控制
 *                                      _ignoreEmpty
 *                                      _ignoreEmptyKey
 * @param array $keyArr         - 自定义缓存key参数数组（如设置，则忽略$args作为缓存组合键名）
 * @param array $addRequestArg  - 缓存key是否自动识别加上 POST GET参数
 *
 * @return mixed      - 同真实被缓存数据的方法返回值
 */
if (! function_exists ( 'cache_method' )) {
    function cache_method($obj, $methodName, $args, $cacheOpt = ['_cacheTime'=> 300], $keyArr=[], $addRequestArg = false) {
        /**
         * 1. 忽略args中的缓存控制字段，仅以$cacheOpt作为缓存控制参数
         */
        foreach ($args as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            if (isset($v['_cacheTime']) || isset($v['_ignoreEmpty'])) {
                unset($args[$k]);
            }
        }

        $time       = time();
        $cacheTime  = $cacheOpt['_cacheTime'];
        //默认当选定结果数据为空时，不缓存结果
        $ignoreEmpty = isset($cacheOpt['_ignoreEmpty']) ? $cacheOpt['_ignoreEmpty'] : true;
        //识别忽略空数据的范围，默认根据全部结果识别是否为空，决定是否缓存
        $ignoreEmptyKey = isset($cacheOpt['_ignoreEmptyKey']) ? $cacheOpt['_ignoreEmptyKey'] : null;

        //识别模式：优先走实时方法调用，当发生指定异常时，再改用缓存数据请求
        $cache4errStep = 0;
        if (!empty($cacheOpt['_cache4errStep'])) {
            $cache4errStep = intval($cacheOpt['_cache4errStep']);
        }

        /**
         * 2. 重写与忽略缓存模式识别
         */
        $globalResetCache = $resetCache = empty($_GET['reset_cache']) ? 0 : 1;	//强制重写cache
        unset($_GET['skip_cache']);
        unset($_GET['reset_cache']);    //跳过键名参数

        isset($keyArr['reset_cache']) && $resetCache = $keyArr['reset_cache'] ? 1 : 0;   //强制重写cache
        unset($keyArr['reset_cache']);

        /**
         * 3. 防止keyArr和 $_GET 中被“ajax cache:false”模式加上自动随机key（以_开头的随机串参数），而干扰缓存
         */
        if ($keyArr) {
            foreach ($keyArr as $k => $v) {
                if (substr($k, 0,1) == '_') {
                    unset($keyArr[$k]);
                }
            }
        }
        if ($addRequestArg) {
            foreach ($_GET as $k => $v) {
                if (substr($k, 0,1) == '_') {
                    unset($_GET[$k]);
                }
            }
        }

        /**
         * 4. 根据参数组装key；并当缓存时间大于0且未重置缓存模式时，取缓存数据
         */
        if (empty($keyArr)) {
            $cacheKey  = md5($methodName . '@' . http_build_query(!$addRequestArg ? $args : array_merge($args, $_GET, $_SERVER['REQUEST_METHOD']=='POST' ? $_POST : array())));
        } else {
            $cacheKey  = md5($methodName . '@' . http_build_query(!$addRequestArg ? $keyArr : array_merge($keyArr, $_GET, $_SERVER['REQUEST_METHOD']=='POST' ? $_POST : array())));
        }
        if ($cacheTime > 0 && !$resetCache && $cache4errStep != 1) {
            $res = (1 === intval($cacheTime)) ? config($cacheKey) : cache($cacheKey);

            if (!empty($res)) {
                $res = unserialize($res);
                if (!empty($res['__cacheTime'])) {
                    //识别过期时间
                    if ($cacheTime < $res['__cacheTime']) {
                        //如果新的缓存时间小于上一次缓存设置时间，则不覆盖原过期时间
                        $cacheTime = $cacheTime;
                    } else {
                        //未过期，返回缓存数据
                        return isset($res['__resData']['content']) ? json($res['__resData']['content']) : $res['__resData'];
                    }
                } else {
                    //兼容原有写法，运行一段时间后再移除
                    return $res;
                }
            }
        }
        //如果是模式：优先及时方法，异常才走缓存的模式 的第二步，当缓存数据没有取到时，则返回空数据
        if (2 == $cache4errStep) {
            return null;
        }

        /**
         * 5. 回调真实方法，获取调用结果并缓存
         */
        //回调$obj方法的参数组织：忽略cache参数ignore_cache 设为true
        $_GET['skip_cache']   = true;
        $_GET['reset_cache']  = true;
        //控制器action输出返回的兼容处理
        ob_start();
        try {
            if ($obj == null) {
                $res = call_user_func_array($methodName, $args);
            } else {
                $_methodArr = explode('::', $methodName);
                $funcName   = end($_methodArr);
                $res = call_user_func_array(array($obj, $funcName), $args);
            }
        } catch (\Exception $e) {
            //识别模式：优先走实时方法调用，当发生指定异常时，再改用缓存数据请求
            if (1 == $cache4errStep) {
                $_GET['skip_cache']   = false;
                $_GET['reset_cache']  = false;
                $cacheOpt['_cache4errStep'] = 2;
                $res = cache_method($obj, $methodName, $args, $cacheOpt, $keyArr, $addRequestArg );
                if (null === $res) {
                    throw $e;
                } else {
                    return $res;
                }
            } else {
                unset($_GET['skip_cache']);		//跳过cache处理
                $_GET['reset_cache'] = $globalResetCache;
                throw $e;
            }
        }
        
        $content = ob_get_contents();
        ob_end_clean();
        $res === null && $res = $content;
        unset($_GET['skip_cache']);		//跳过cache处理
        $_GET['reset_cache'] = $globalResetCache;

        //识别忽略空数据的范围，选定结果范围为空，则跳过缓存，直接返回结果
        if ($ignoreEmpty) {
            if (null === $ignoreEmptyKey && empty($res)) {
                return $res;
            }
            if (is_array($res) && null !== $ignoreEmptyKey && empty($res[$ignoreEmptyKey])) {
                return $res;
            }
        }

        //缓存数据并返回
        if ($cacheTime > 0) {
            //tp5.1版本的 \think\response 对象有匿名函数，不能序列化
            if (is_subclass_of($res, '\think\response')) {
                $response_res = [
                    'type' => 'response',
                    'content' => json_decode($res->getContent(), true),
                ];
                $res_arr = [
                    '__cacheTime' => $cacheTime,
                    '__resData'   => $response_res,
                ];
            } else {
                $res_arr = [
                    '__cacheTime' => $cacheTime,
                    '__resData'   => $res,
                ];
            }
            if (is_laravel()) {
                (1 === intval($cacheTime)) ? config([$cacheKey => serialize($res_arr)]) : cache([$cacheKey => serialize($res_arr)], $cacheTime/60);
            } else {
                (1 === intval($cacheTime)) ? config($cacheKey, serialize($res_arr)) : cache($cacheKey, serialize($res_arr), $cacheTime);
            }

        }
        return $res;
    }
}

/**
 * 通用方法调用，用于指定当异常发生时，是否取缓存数据。通过$cache4err进行切换模式
 * @param $obj
 * @param $methodName
 * @param $args
 * @param bool $cache4err
 * @return false|mixed|string
 * @throws Exception
 */
if (! function_exists ( 'call_func_err_cache' )) {
    function call_func_err_cache($obj, $methodName, $args, $cache4err = true, $cache_time = 864000)
    {
        if ($cache4err) {
            $cacheOpt = ['_cacheTime' => $cache_time, '_cache4errStep' => 1];
            $res = cache_method($obj, $methodName, $args, $cacheOpt);
        } else {
            if ($obj == null) {
                $res = call_user_func_array($methodName, $args);
            } else {
                $_methodArr = explode('::', $methodName);
                $funcName = end($_methodArr);
                $res = call_user_func_array(array($obj, $funcName), $args);
            }
        }

        return $res;
    }
}

/**
 * 分图书馆aid存放的配置获取
 * @param integer $aid 图书馆aid
 * @param string $key
 * @return mixed
 * @throws \EasyUtils\Kernel\exception\BizException
 */
function extra_conf($aid, $key='')
{
    if (empty($key)) {
        return config("lib_{$aid}");
    }

    $key_arr = explode('.', $key);
    $config  = config("lib_{$aid}.{$key_arr[0]}");

    switch (count($key_arr)) {
        case 1:
            return $config;
        case 2:
            return $config[$key_arr[1]];
        case 3:
            return $config[$key_arr[1]][$key_arr[2]];
        case 4:
            return $config[$key_arr[1]][$key_arr[2]][$key_arr[3]];
        default:
            biz_exception('key不能超过4层');
    }
}