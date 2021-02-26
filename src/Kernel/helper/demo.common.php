<?php
/*
 * 应用公共文件
 *
 * common.php
 *
 * 用于整个所有项目的公用处理
 */

// +----------------------------------------------------------------------
// | 加载所有项目公用的助手函数
// | (本模块应用所需要的其他助手函数，请在项目自己的公共配置中加载)
// +----------------------------------------------------------------------
load_helper('time');    // 时间相关
load_helper('log');     // 调试工具
load_helper('net');     // 网络相关，含通用http请求方法
load_helper('cache');   // 通用缓存相关


// +----------------------------------------------------------------------
// | 所有应用公用的助手函数
// +----------------------------------------------------------------------

/**
 * 加载公用位置的助手函数文件
 * (公用位置按优先级含：application/common/helper， extend/EasyUtils/helper)
 * @param string $helper_name 助手函数文件名（可不含文件扩展名）
 */
function load_helper($helper_name) {
    $helper_name = str_replace(['.php', 'helper_'], '', $helper_name);
    $ds = DIRECTORY_SEPARATOR;
    
    $path = Env::get('app_path') . "common{$ds}helper{$ds}{$helper_name}.php";
    if (file_exists($path)) {
        require_once $path;
        return;
    }
    
    $path = Env::get('extend_path') . "EasyUtils{$ds}helper{$ds}helper_{$helper_name}.php";
    if (file_exists($path)) {
        require_once $path;
        return;
    }
    throw new \Exception("助手文件不存在");
}
