<?php
/**
 * MessageData.php
 * 2020-02-12  wangpeng<wangpeng@bookgoal.com.cn>
 */

namespace EasyUtils\MessageSender\Service;


class MessageData
{
    /**
     * @var mixed 用户名称
     */
    public $user_name;

    /**
     * @var mixed 标题
     */
    public $title;

    /**
     * @var mixed 日期
     */
    public $date;

    /**
     * @var mixed 金额
     */
    public $amount;

    /**
     * @var mixed 电话
     */
    public $phone_number;

    /**
     * @var mixed 编号
     */
    public $serial_number;

    /**
     * @var mixed 结果
     */
    public $result;

    /**
     * @var mixed 内容
     */
    public $content;

    /**
     * @var mixed 备注
     */
    public $remark;

    /**
     * @param mixed $value
     **/
    public function setUserName($value)
    {
        $this->user_name = $value;
    }
    /**
     * @return mixed
     **/
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @param mixed $value
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $value
     */
    public function setDate($value)
    {
        $this->date = $value;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $value
     */
    public function setAmount($value)
    {
        $this->amount = $value;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $value
     */
    public function setPhoneNumber($value)
    {
        $this->phone_number = $value;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param mixed $value
     */
    public function setSerialNumber($value)
    {
        $this->serial_number = $value;
    }

    /**
     * @return mixed
     */
    public function getSerialNumber()
    {
        return $this->serial_number;
    }

    /**
     * @param mixed $value
     */
    public function setResult($value)
    {
        $this->result = $value;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $value
     */
    public function setContent($value)
    {
        $this->content = $value;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $value
     */
    public function setRemark($value)
    {
        $this->remark = $value;
    }

    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
    }
}