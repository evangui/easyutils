<?php
/*
 * 业务通用异常类文件
 *
 * BizException.php
 * 2019年1月15日 下午14:07:04  guiyj<guiyj007@gmail.com>
 *
 * 这里封装了基于业务模块的抽象常量类基本的核心get方法。
 * - 子类需要继承重新定义自己的类常量与$constsMap
 * - 请注意保证所有常量值唯一，否则文字提示可能不能正确定位
 */
namespace EasyUtils\Kernel\exception;

/**
 * 通用的业务异常处理类
 */
class BizException extends \Exception
{
    /**
     * 保存异常页面显示的额外Debug数据
     * @var array
     */
    protected $data = [];

    /**
     * BizException constructor.
     * @access public
     * @param  string   $message  异常消息  
     * @param  int       $code    异常码
     * @param  array    $data     需要记录的额外自定义信息
     */
    public function __construct($message = null, $code=1, $data=[])
    {
        $this->message = $message;
        $this->code    = $code;
        $this->setData('data', $data);
    }

    /**
     * 设置异常额外的Debug数据
     * 数据将会显示为下面的格式
     *
     * Exception Data
     * --------------------------------------------------
     * Label 1
     *   key1      value1
     *   key2      value2
     * Label 2
     *   key1      value1
     *   key2      value2
     *
     * @access protected
     * @param  string $label 数据分类，用于异常页面显示
     * @param  array  $data  需要显示的数据，必须为关联数组
     */
    final protected function setData($label, array $data)
    {
        $this->data[$label] = $data;
    }

    /**
     * 获取异常额外Debug数据
     * 主要用于输出到异常页面便于调试
     * @access public
     * @return array 由setData设置的Debug数据
     */
    final public function getData($label = '')
    {
        if (empty($label)) {
            return $this->data;
        }
        return $this->data[$label];
    }

    /**
     * 获取异常额外Debug数据
     * 主要用于输出到异常页面便于调试
     * @access public
     * @return array 由setData设置的Debug数据
     */
    final public function getApiOutput()
    {
        return isset($this->data['data']['output']) ? $this->data['data']['output'] : '';
    }
}
