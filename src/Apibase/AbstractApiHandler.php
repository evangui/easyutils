<?php
/*
 * api执行类抽象定义
 *
 * AbstractApiHandler.php
 * 2019年1月28日 下午16:27:06  guiyj<guiyj007@gmail.com>
 *
 * 用于所有模块接口按业务功能分组后，进行组级接口处理类的基类与抽象定义
 */
namespace EasyUtils\apibase;

use EasyUtils\Kernel\constant\ApiCodeConst;
use think\facade\Config;
use think\facade\Log;

/**
 * api执行类抽象
 */
abstract class AbstractApiHandler
{
    /**
     * 输入参数数据处理对象（可用于md5与sign校验的接口）
     * @var ReqData
     */
    protected $reqObj;

    /**
     * 接口系统标致: 微信图书馆
     * @var string
     */
    const API_SYS_WXLIB = 'wxlib';

    /**
     * 接口系统标致: 微信图书馆
     * @var string
     */
    const API_SYS_WXLIB_V2 = 'wxlib_v2';

    /**
     * 接口系统标致: 图书馆系统
     * @var string
     */
    const API_SYS_LIBSYS = 'libsys';

    const API_SYS_DB = 'db';

    /**
     * 接口请求系统名
     * @var string
     */
    public $apiSysCode;

    /**
     * 是否加载过本包的配置文件
     * @var string
     */
    public static $loadConfig = false;

    /**
     * 默认错误重连次数
     */
    protected $errorRetryTimes = 1;

    /**
     * 是否自动识别检测接口返回状态码，将非正常码当做业务异常抛出
     */
    protected $autoCheckBizException = true;

    /**
     * 是否期望请求返回的data键名存在，且是列表
     * @var bool
     */
    protected $transData2List = false;

    /**
     * 调用的原始返回结果
     * @var mixed
     */
    protected $ioRes;

    /**
     * 获取调用的原始返回结果
     * @return string
     */
    public function getIoRes()
    {
        return $this->ioRes;
    }

    /**
     * 设置调用的原始返回结果
     * @param string $ioRes
     */
    public function setIoRes($ioRes)
    {
        $this->ioRes = $ioRes;
    }

    /**
     * 获取api的系统识别码。
     * 子类需要返回其中之一  self::API_SYS_WXLIB, self::API_SYS_LIBSYS
     */
    abstract public function getApiSysCode();

    public function __construct() {}

    /**
     * 设置本类持有的请求数据对象
     * @param ReqData $reqObj
     * @return \EasyUtils\Apibase\ReqData
     */
    public function setReqObj(ReqData $reqObj)
    {
        return $this->reqObj = $reqObj;
    }

    /**
     * 获取本类持有的请求数据对象，如不存在则初始化返回
     * @return \EasyUtils\Apibase\ReqData
     */
    public function getReqObj()
    {
        if (null === $this->reqObj) {
            $this->reqObj = new ReqData();
        }
        return $this->reqObj;
    }

    /**
     * 本方法为调用签名验证接口示例
     *
     * @param ReqData|array $data   - 请求数据对象，或请求参数数组
     * @return 成功时返回，其他抛异常
     * @example
     *   //方式一：直接传入array当做参数
     *   $params = ['uid'=>1, 'nickname'=>'桂亚军'];
     *   $r = UserFacade::signApiDemo($params);
     *
     *   //方式二：传递ReqData对象当做参数
     *   $reqDataObj = new ReqData();
     *   $reqDataObj->set('uid', 1)->set('nickname', '桂亚军');
     *   $r = UserFacade::signApiDemo($reqDataObj);
     */
    public function signApiDemo($data=[])
    {
        $uri = "index/index/signApiDemo";

        /**
         * 兼容处理：如果有传数组形式的$data，则用data覆盖用户可能通过where方法设置的入参
         */
        if (is_array($data)) {
            $this->setWhereByArray($data, ['uid', 'nickname']);
        }

        //检测必填参数
        if(!$this->isKeySet('uid')) {
            throw new \Exception("缺少必填参数uid！");
        }
        if(!$this->isKeySet('nickname')) {
            throw new \Exception("缺少必填参数nickname！");
        }
        //设置输入参数签名
        $this->setSign();
        $params = $this->getInput();

        return $this->request($uri, $params);
    }

    /**
     * 通过where连接符，组合查询参数
     * @param string $name
     * @param mixed $val
     * @return \EasyUtils\Apibase\AbstractApiHandler
     */
    public function where($name, $val)
    {
        $this->getReqObj()->set($name, $val);
        return $this;
    }

    /**
     * 加载统一框架的接口配置文件
     */
    public static function loadConfig()
    {
        if (!self::$loadConfig) {
            Config::load(__DIR__ . '/../common/config/api_config.php');
            self::$loadConfig = true;
        }
    }

    /**
     * 通过将数组请求数据，覆盖转化本类持有的请求数据对象
     * @param array $data_arr   数组请求数据
     * @param array $keys       覆盖转化的请求数据对象数据键名
     */
    protected function setWhereByArray(array $data_arr, array $keys)
    {
        if (!is_array($data_arr) || empty($data_arr) || empty($keys)) {
            return ;
        }
        foreach ($keys as $key) {
            if (isset($data_arr[$key])) {
                $this->where($key, $data_arr[$key]);
            }
        }
    }

    /**
     * 设置入口参数对象的签名参数
     * @return array|\EasyUtils\Apibase\AbstractApiHandler
     **/
    protected function setSign()
    {
        $reqObj = $this->getReqObj();
        $reqObj->setAppid($reqObj->getAppid())->setSign();
        return $this;
    }

    /**
     * 通过数组形式，返回接口入口参数对象的数据
     * @return array|\EasyUtils\Apibase\AbstractApiHandler
     */
    protected function getInput()
    {
        return $this->getReqObj()->getValues();
    }

    /**
     * 检测接口入口参数，是否在输入参数对象中存在
     * @return \EasyUtils\Apibase\AbstractApiHandler
     */
    protected function isKeySet($key)
    {
        return $this->getReqObj()->isKeySet($key);
    }

    /**
     * 设置接口数据通信异常重试次数：含网络异常，服务端异常
     * @param $times
     * @return $this
     */
    protected function setErrorRetryTimes($times) {
        $this->errorRetryTimes = $times;
        return $this;
    }

    /**
     * 设置自动识别检测接口返回状态码，将非正常码当做业务异常抛出
     * @param $auto_throw_biz_exception
     */
    protected function setAutoBizException($auto_throw_biz_exception)
    {
        $this->autoCheckBizException = $auto_throw_biz_exception;
    }

    /**
     * 是否期望请求返回的data键名存在，且是列表
     * @param bool $datalist
     * @return $this
     */
    public function expectDataList($datalist = true) {
        $this->transData2List = $datalist;
        return $this;
    }

    /**
     * curl请求对外接口，带错误重试与异常识别
     *
     * @param string $uri         请求资源路径，如 uar/library/searchBook
     * @param array $param        接口请求参数
     * @param integer $try_times  接口遇到网络错误及约定错误码时，重试请求次数
     * @param integer $timeout    接口响应超时时间，单位：秒
     * @throws ApiException
     * @return array
     */
    protected function request($uri, $param, $timeout=10, $try_times=1)
    {
        $this->setErrorRetryTimes($try_times);
        $res = $this->doRequest($uri, $param, $timeout);
        $res = $this->formatRetData($res);

        if (!empty($_GET['_api_debug'])) {
            ve($this->getIoRes());
        }

        // 异常状态处理
        if (!isset($res['code'])) {
            throw new ApiException('网络请求失败', ApiCodeConst::NETWORK_ERR, $this->getIoRes());
        }
        $this->checkBizException($res);
        return $res;
    }

    /**
     * 识别接口服务端系统，是否返回正常的状态码，如非正常，则统一当做接口异常抛出
     * @param $res
     * @throws ApiException
     */
    protected function checkBizException($res)
    {
        if ($res['code'] != ApiCodeConst::BIZ_SUCCESS) {
            throw new ApiException($res['msg'], $res['code'], $this->getIoRes());
        }
    }


    /**
     * curl请求对外接口
     *
     * @param string $uri   请求资源路径，如 uar/library/searchBook
     * @param array $param  接口请求参数
     * @return array
     */
    protected function doRequest($uri, $param, $timeout=10)
    {
        // 组装接口请求地址
        $apiSysCode = $this->getApiSysCode();
        if (null === config('apiurl.libsys')) {
            self::loadConfig();
        }

        switch ($apiSysCode) {
            case self::API_SYS_LIBSYS:
                $url = config('apiurl.libsys') . $uri;
                break;
            case self::API_SYS_WXLIB:
                $url = config('apiurl.wxlib') . $uri;
                break;
            case self::API_SYS_WXLIB_V2:
                $url = config('apiurl.wxlib_v2') . $uri;
                break;
            default:
                throw new \Exception('未指定接口系统');
        }

        // 请求接口
        $time_start = microtime(true);
        $current_error_times = 0;
        $res_arr = [];
        while (!isset($res_arr['code']) && $current_error_times < $this->errorRetryTimes) {
            $res = http_curl($url, $param, null, $timeout);
            $res_arr = json_decode($res,true);
            $current_error_times++;
        }

        $used_time  = number_format(microtime(true) - $time_start, 4);
        trace("【ApiHandler】输入数据>>>>>>>{$url}：" .var_export($param,true));
        trace("【ApiHandler】{$used_time}s 输出数据<<<<<<<{$url}：\r\n{$res}\r\n" . var_export($res_arr, true));

        $err_data = [
            'url' => $url,
            'param' => $param,
            'used_time' => $used_time,
            'output' => $res,
            'err_code' => !empty($res_arr['code']) ? $res_arr['code'] : null,
            'err_msg' => !empty($res_arr['msg']) ? $res_arr['msg'] : null,
        ];
        $this->setIoRes($err_data);

        return $res_arr;
    }

    protected function formatRetData($res)
    {
        /**
         * 将参数格式进行统一处理
         */
        //（2）200状态码的，成功code=0替换
        200 === intval($res['code']) && $res['code'] = 0;
        if (isset($res['data']) && !is_array($res['data']) && $this->transData2List) {
            $res['data'] = empty($res['data']) ? [] : [$res];
        }

        //沿用原code与data, msg字段名统一
        $res = [
            'code' => $res['code'],
            'data' => isset($res['data']) ? $res['data'] : ($this->transData2List ? [] : ''),
            'msg'  => isset($res['message']) ? $res['message'] :
                (isset($res['msg']) ? $res['msg'] : ''),
        ];
        $this->transData2List = false;
        return $res;
    }

}

