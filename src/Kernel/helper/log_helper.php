<?php
// 工具函数 - 日志相关


/**
 * 记录日志信息（兼容laravel项目）
 * @param mixed     $log log信息 支持字符串和数组
 * @param string    $level 日志级别
 * @return array|void
 */
function log_trace($log, $level = 'info')
{
    if (!function_exists('trace')) {
        if (is_laravel()) {
            \Illuminate\Support\Facades\Log::$level($log);
        } else {
            biz_exception('trace function should defined yourself!');
        }
    } else {
        trace($log, $level);
    }
}

//调试方法
function mylog($log){
   log_file($log);
}

//wl调试方法
function wllog($log){
   	$path=ROOT_PATH . "runtime/mylog/".date('Ym',time());
    createFolder($path); 
	if ( empty($file) ) $file = date("Ymd").'wl' . ".log";
	$file = $path.'/'. $file;

	if (is_array($log) || is_object($log)) $log = print_r($log,true);
    file_put_contents($file,$log."\n",FILE_APPEND); 
}

//lis调试方法
function mymylog($log){
   	$path=ROOT_PATH . "runtime/mylog/".date('Ym',time());
    createFolder($path); 
	if ( empty($file) ) $file = date("Ymd").'lis' . ".log";
	$file = $path.'/'. $file;

	if (is_array($log) || is_object($log)) $log = print_r($log,true);
    file_put_contents($file,$log."\n",FILE_APPEND); 
}
function mymysql($module){
   mylog($module->getLastSql());
}

// 页面输出 - behero
function log_page($data) {
	if ( is_array($data) || is_object($data) ) {
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	} else {
		echo $data;
	}
}
// 文件输出 - behero
function log_file($log, $desc="", $file="") {
	$path=ROOT_PATH . "runtime/mylog/".date('Ym',time());
    createFolder($path); 
	if ( empty($file) ) $file = date("Ymd") . ".log";
	$file = $path.'/'. $file;

	if (is_array($log) || is_object($log)) $log = print_r($log,true);
    	file_put_contents($file,$log."\n",FILE_APPEND);
}


if (! function_exists ( 'v' )) {
    function v($var, $die = 1) {
        echo "<pre>";
        var_dump ( $var );
        $die && die ();
    }
}

if (! function_exists ( 've' )) {
    function ve($var, $die = 1) {
        echo "<pre>";
        var_export($var);
        $die && die ();
    }
}

if (! function_exists ( 'p' )) {
    function p($var, $die = 1) {
        echo "<pre>";
        print_r ( $var );
        $die && die ();
    }
}