<?php
/*
 * 微信小程序业务方法封装
 * 具体可用功能，可参见 \EasyUtils\OpenPlatform\Authorizer\MiniProgram\Application下的句柄对象
 * 开发文档地址：https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Mini_Programs/Intro.html
 *
 * Wxapp.php
 * 2019-04-17 16:41  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Wechat\Service;
use EasyUtils\Kernel\constant\WeixinConst;
use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\User\Service\UserFacade;
use EasyUtils\Factory;

/**
 *  小程序服务商方法
 */
class OpenWxapp extends Wxapp
{
    /**
     * @var \EasyUtils\OpenPlatform\Application
     */
    private static $openPlatformHandler;

    /**
     * @param string $wxapp_name    小程序名
     * @param int $aid  图书馆aid(服务商模式才有用，默认为0表示非服务商模式)
     * @return OpenWxapp
     */
    public static function getInstance($wxapp_name = '', $aid=0)
    {
        self::setOpenPlatformHandler();
        return parent::getInstance($wxapp_name, $aid);
    }

    /**
     * 获取代码草稿列表
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function getDrafts(){
        return self::getOpenPlatformHandler()->code_template->getDrafts();
    }

    /**
     * 将草稿添加到代码模板库
     * 可以通过获取草稿箱中所有的草稿得到草稿 ID；调用本接口可以将临时草稿选为持久的代码模板
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function addDraftToTemplate(int $draftId){
        return self::getOpenPlatformHandler()->code_template->createFromDraft($draftId);
    }

    /**
     * 获取代码模板列表
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function listCodeTemplates(){
        return self::getOpenPlatformHandler()->code_template->list();
    }

    /**
     * 删除指定代码模版
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function deleteCodeTemplate(int $draftId){
        return self::getOpenPlatformHandler()->code_template->delete($draftId);
    }

    /**
     * 设置服务器域名
     * @param array $params
     * @example
     * [
     *   "action" => "add",   //add	添加 delete	删除 set	覆盖  get	获取
     *   "requestdomain" => ["https://www.qq.com", "https://www.qq.com"],
     *   "wsrequestdomain" => ["wss://www.qq.com", "wss://www.qq.com"],
     *   "uploaddomain" => ["https://www.qq.com", "https://www.qq.com"],
     *   "downloaddomain" => ["https://www.qq.com", "https://www.qq.com"]
     * ]
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function serverDomain(array $params){
        return $this->easyHandler()->domain->modify($params);
    }

    /**
     * 设置业务域名
     * @param array $domains ["https://www.qq.com", "https://www.qq.com"]
     * @param string $action  //add	添加 delete	删除 set	覆盖  get	获取
     *
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function webviewDomain(array $domains, $action = 'add'){
        return $this->easyHandler()->domain->setWebviewDomain($domains, $action);
    }

    /**
     * 上传小程序代码
     * @param int $templateId   代码库中的代码模版 ID
     * @param string $extJson   第三方自定义的配置，即：ext.json的内容。见：https://developers.weixin.qq.com/miniprogram/dev/devtools/ext.html#%E5%B0%8F%E7%A8%8B%E5%BA%8F%E6%A8%A1%E6%9D%BF%E5%BC%80%E5%8F%91
     * @param string $version   代码版本号，开发者可自定义（长度不要超过 64 个字符）
     * @param string $description   代码描述，开发者可自定义
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function commitCode(int $templateId, string $extJson, string $version, string $description){
        $app = $this->easyHandler();
        return $app->code->commit($templateId, $extJson, $version, $description);
    }

    /**
     * 获取已上传的代码的页面列表
     * 通过本接口可以获取由第三方平台上传小程序代码的页面列表；用于提交审核的审核项 的 address 参数
     *
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function getPage(){
        return $this->easyHandler()->code->getPage();
    }

    /**
     * 获取体验版二维码
     * 调用本接口可以获取小程序的体验版二维码
     * @param string $page 指定二维码扫码后直接进入指定页面并可同时带上参数）
     * @return \EasyUtils\Kernel\Http\Response
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function getQrCode($path = null){
        return $this->easyHandler()->code->getQrCode($path);
    }

    /**
     * 提交审核
     * 在调用上传代码接口为小程序上传代码后，可以调用本接口，将上传的代码提交审核
     *
     * @param array $itemList   审核项列表（选填，至多填写 5 项）
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function submitAudit(array $itemList ){
        return $this->easyHandler()->code->submitAudit($itemList);
    }

    /**
     * 查询指定发布审核单的审核状态
     * @param int $auditId
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function getAuditStatus(int $auditId ){
        return $this->easyHandler()->code->getAuditStatus($auditId);
    }

    /**
     * 查询最新一次提交的审核状态
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function getLatestAuditStatus( ){
        return $this->easyHandler()->code->getLatestAuditStatus();
    }

    /**
     * 小程序审核撤回
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function undoCodeAudit(){
        return $this->easyHandler()->code->withdrawAudit();
    }

    /**
     * 发布已通过审核的小程序
     * 发布最后一个审核通过的小程序代码版本
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function release(){
        return $this->easyHandler()->code->release();
    }

    /**
     * 版本回退
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     */
    public function rollbackRelease(){
        return $this->easyHandler()->code->rollbackRelease();
    }

    /**
     * 绑定体验者
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function bindTester(string $wechatId){
        return $this->easyHandler()->tester->bind($wechatId);
    }

    /**
     * 解绑体验者
     * @return array|\EasyUtils\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function unbindTester(string $wechatId){
        return $this->easyHandler()->tester->unbind($wechatId);
    }

    public function listTester(){
        return $this->easyHandler()->tester->list();
    }

    private static function setOpenPlatformHandler()
    {
        $config = config("wxapp.open_platform");
        self::$openPlatformHandler = Factory::openPlatform($config);
    }


    /**
     * @return \EasyUtils\OpenPlatform\Application
     */
    public static function getOpenPlatformHandler()
    {
        return self::$openPlatformHandler ;
    }

}
