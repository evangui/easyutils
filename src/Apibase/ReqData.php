<?php
/*
 * 请求数据类
 *
 * UserCenter.php
 * 2019年1月28日 11:23:10  guiyj<guiyj007@gmail.com>
 *
 * - 该类封装了请求数据的get-set基本方法
 * - 封装签名生成算法（参数键名升序+key+MD5）
 */
namespace EasyUtils\apibase;

/**
 * 请求数据类
 */
class ReqData
{
    protected $values = [];
    
    /**
     * 接口请求系统参数
     * @var array
     */
    protected $config = [
        'appid' => '10001',
        'secret' => 'B14A0AD51AA5044CE3767018530EA2FB', 
    ];
    
    /**
     * 初始化数据对象，并初始化接口系统参数
     * @param array $config
     */
    public function __construct($config=[]) 
    {
        if (isset($config['appid'])) {
            $this->config = $config;
        }
    }
    
    /**
     * 设置接口系统参数
     * @param array $config
     * @throws \Exception
     * @return \EasyUtils\Apibase\ReqData
     */
    public function setConfig($config=[]) 
    {
        if (empty($config['appid']) || empty($config['secret'])) {
            throw new \Exception('appid与secret不能为空');
        }
        $this->config = $config;
        return $this;
    }
    
    /**
     * 设置接口系统参数中的appid
     * @param array $config
     * @throws \Exception
     * @return \EasyUtils\Apibase\ReqData
     */
    public function setAppid($appid='') 
    {
        if (!empty($key)) {
            $this->config['appid'] = $appid;
        }
        $this->set('appid', $this->config['appid']);
        return $this;
    }
    
    /**
     * 获取接口系统参数中的appid
     * @return mixed
     */
    public function getAppid() 
    {
        return $this->config['appid'];
    }
    
    /**
     * 设置接口系统参数中的secret
     * @return mixed
     */
    public function getSecret() 
    {
        return $this->config['secret'];
    }
    
    
    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function set($name, $val)
    {
        $this->values[$name] = $val;
        return $this;
    }
    
    /**
     * 判断参数名是否存在
     * @return true 或 false
     **/
    public function isKeySet($key_name)
    {
        return array_key_exists($key_name, $this->values);
    }
    
    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function setSign($simple_sign = false)
    {
        if ($simple_sign) {
            $sign = $this->makeSimpleSign();
        } else {
            $sign = $this->makeSign();
        }

        $this->values['sign'] = $sign;
        return $sign;
    }
    
    /**
     * 获取签名，详见签名生成算法的值
     * @return
     **/
    public function getSign()
    {
        return $this->values['sign'];
    }
    
    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function isSignSet()
    {
        return array_key_exists('sign', $this->values);
    }
    
    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        
        $buff = trim($buff, "&");
        return $buff;
    }
    
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($config='')
    {
        empty($config) && $config = $this;
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->toUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&secret=" . $config->getSecret();
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 生成简单模式的签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSimpleSign($config='')
    {
        empty($config) && $config = $this;
        //签名步骤一：按字典序排序参数
        $string = 'appid=' . $this->values['appid'];
        $string .= '&timestamp=' . $this->values['timestamp'];
        $string .= '&method=' . $this->values['method'];
        //签名步骤二：在string后加入KEY
        $string = $string . "&secret=" . $config->getSecret();
        //签名步骤三：MD5加密
        $string = md5($string);

        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
//        v($result);
        return $result;
    }
    
    /**
     * 获取设置的值
     */
    public function getValues()
    {
        return $this->values;
    }
    
    
    /**
     * 检测签名
     */
    public function checkSign($input_array, $simple_sign=false)
    {
        $this->fromArray($input_array);
        if(!$this->isSignSet()){
            throw new ApiException("签名错误！");
        }
        if ($simple_sign) {
            $sign = $this->makeSimpleSign();
        } else {
            $sign = $this->makeSign();
        }
        if($this->getSign() == $sign){
            //签名正确
            return true;
        }
        return false;
    }
    
    /**
     * 使用数组初始化
     * @param array $array
     */
    public function fromArray($array) 
    {
        $this->values = $array;
    }
    
}
