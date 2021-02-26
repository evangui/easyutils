<?php
/*
 * 微信小程序业务方法封装
 *
 * Wxapp.php
 * 2019-04-17 16:41  guiyj<guiyj007@gmail.com>
 *
 */
namespace EasyUtils\Wechat\Service;
use EasyUtils\Kernel\Constant\WeixinConst;
use EasyUtils\Kernel\Support\HandlerFactory;
use EasyUtils\User\Service\UserFacade;

/**
 *  小程序用通用方法
 */
class Wxapp
{
    /**
     * 公众号模板消息类型，与数据库表mp_template字段type一致
     *  1 openid, 2 uid, 3 reader_id
     */
    const MSG_TOUSER_TYPE_OPENID    = 1;
    const MSG_TOUSER_TYPE_UID       = 2;
    const MSG_TOUSER_TYPE_READER_ID = 3;

    protected static $self;
    protected $redisConf = ['index' => '2'];   //redis配置参数
    private $wxappName = WeixinConst::WXAPP_NAME_WXLIB;
    private $aid = 0;

    protected function __construct($wxapp_name, $aid=0) {
        if ($wxapp_name) {
            $this->wxappName = $wxapp_name;
            $this->aid = $aid;
        }
    }

    /**
     * @param string $wxapp_name    小程序名
     * @param int $aid  图书馆aid(服务商模式才有用，默认为0表示非服务商模式)
     * @return Wxapp
     */
    public static function getInstance($wxapp_name = '', $aid=0)
    {
        if (!self::$self) {
            $called_class = get_called_class();
            self::$self = new $called_class($wxapp_name, $aid);
        }
        return self::$self;
    }

    /**
     * 获取当前实例的小程序名'_cacheTime' =>
     * @return string
     */
    protected function getWxappName() {
        return $this->wxappName;
    }

    /**
     * 获取当前实例的aid
     * @return string
     */
    protected function getAid() {
        return $this->aid;
    }


    /**
     * 获取form_id的缓存键名
     * @param $openid
     * @return string
     */
    protected function getFormIdCacheKey($openid)
    {
        return "wxMa-formIds{$openid}";
    }

    /**
     * 发送小程序模板消息
     * @param string $tpl_type 模板消息类型，对应管理后台的消息类型，请用如下方式获取：EasyUtils\Kernel\Constant\WxTemplateType
     * @param array $data 消息主体,eg:
     * [
     *  'keyword1' => 'VALUE',
     *  'keyword2' => 'VALUE2',
     * ]
     * @param mixed $to_user    接收人唯一标识。可为用户uid，openid，读者证号。具体通过$touser_type来指定
     * @param int $touser_type  接收人唯一标识类型（1 openid, 2 uid, 3 reader_id），请用如下方式设置：We::MSG_TOUSER_TYPE_OPENID
     * @param string $form_id   form_id 如果为空，则从以前收集的fromid中取。建议传
     * @param string $page      消息点击page
     * @return array            错误消息列表
     * @throws \EasyUtils\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function sendTemplateMessage(
        $tpl_type,
        $data,
        $to_user,
        $touser_type=Wxapp::MSG_TOUSER_TYPE_OPENID,
        $form_id='',
        $page = '',
        $aid = 0)
    {
        $template  = $this->getTemplates($this->wxappName, $tpl_type);
        if (empty($template)) {
            biz_exception('模板类型错误');
        }

        /**
         * 2 找到openid
         */
        if (self::MSG_TOUSER_TYPE_OPENID == $touser_type) {
            $openid_list = is_array($to_user) ? $to_user : [$to_user];
        } elseif (self::MSG_TOUSER_TYPE_UID == $touser_type) {
            //根据userid找openid（多个）
            $openid_list = UserFacade::getBgOpenidByUid($to_user);
        } elseif (self::MSG_TOUSER_TYPE_READER_ID == $touser_type) {
            //根据reader_id 找openid（多个）
            $openid_list = UserFacade::getOpenIdByAidReaderId($aid, $to_user, 1);
        }
        if (empty($openid_list)) {
            biz_exception("openid为空");
        }

        $handler = $this->easyHandler()->template_message;
        $tpl_msg = [
            'template_id'   => $template['template_id'],
            'page'          => $page ?: $template['page'],
            'data'          => $data,
        ];

        /**
         * 3 列表循环发送，记录返回错误消息
         */
        $ret_errors = [];
        foreach ($openid_list as $openid) {
            //从队列取不为空的formid
            !$form_id && $form_id = $this->loopGetFromid($openid);
            if (!$form_id) {
//                biz_exception("缺少formId");
                $ret_errors[] = "{$openid}:缺少formId";
                continue;
            }

            $tpl_msg['touser'] = $openid;
            $tpl_msg['form_id'] = $form_id;

            $response = $handler->send($tpl_msg);
            if (!isset($response['errcode'])) {
                $ret_errors[] = "{$openid}:未知异常";
                continue;
            }
            if (0 == $response['errcode']) {    //发送成功
                continue;
            }

            if (is_array($response) && !empty($response['errcode'])) {
                /**
                 * 如果form_id无效，则尝试再取一次form发送
                 * 无效formid: 41028	form_id不正确，或者过期. 41029	form_id已被使用
                 */
                if (in_array($response['errcode'], [41028, 41029])) {
                    $form_id = $this->loopGetFromid($openid);
                    if ($form_id) {
                        $tpl_msg['form_id'] = $form_id;
                        $response = $handler->send($tpl_msg);
                        if (0 == $response['errcode']) {
                            continue;
                        }
                    }
                }

                $ret_errors[] = $openid . ':' . $response['errcode'] . '-' .$response['errmsg'];
            }
        }

        return $ret_errors;
    }

    /**
     * 从队列取form id
     * @param string $openid
     * @return string
     */
    public function loopGetFromid($openid)
    {
        $form_id = '';
        //从队列取formid
        $loop = 5;
        while (!$form_id && $loop >0) {
            $form_id = $this->getFormId($openid);
            if ($form_id) {
                break;
            }
            $loop--;
        }
        return $form_id;
    }

    /**
     * 保存推送码formId
     *
     * @param openid
     * @param formIds
     * @return
     */
    public function saveFormIds($openid, $form_ids) {
        $redis = $this->redisHandler();
        $time = time();
        $cache_key = $this->getFormIdCacheKey($openid);
        is_string($form_ids) && $form_ids = explode(',', $form_ids);
        foreach ($form_ids as $form_id) {
            //测试环境的formid忽略
            if (str_exist($form_id, 'the formId')) {
                continue;
            }
            $form_id = json_encode([$form_id, $time]);
            $res = $redis->lpush($cache_key, $form_id);
        }
        $redis->expireAt($cache_key, $time+86400*7);
        return true;
    }


    //1.取出一个可用的用户openId对应的推送码
    public function getFormId($openid){
        $redis = $this->redisHandler();
        $cache_key = $this->getFormIdCacheKey($openid);
        while ($form_id = $redis->lPop($cache_key)) {
            list($form_id, $expire) = json_decode($form_id);
            if ($expire + 86400*7 - 7200 >= time()) {
                return $form_id;
            }
        }
        return '';
    }

    /**
     * @param int $tpl_type
     * @param array $data
     * @param string $to_user
     * @param string $touser_type
     * @param string $page
     * @param int $aid
     * @return array
     */
    public function sendSubscribeMessage($tpl_type, $data, $to_user, $touser_type='', $page = '', $aid = 0)
    {
        /**
         * 1 获取消息模板
         */
        $template  = self::getMiniTemplate($tpl_type, $this->wxappName);
        if (empty($template)) {
            biz_exception('模板类型错误');
        }

        /**
         * 2 找到openid
         */
        switch ($touser_type) {
            case self::MSG_TOUSER_TYPE_UID :    //根据userid找openid（多个）
                $openid_list = UserFacade::getBgOpenidByUid($to_user);
                break;
            case self::MSG_TOUSER_TYPE_READER_ID :  //根据reader_id 找openid（多个）
                $openid_list = UserFacade::getOpenIdByAidReaderId($aid, $to_user, 1);
                break;
            default :   //默认openid
                $openid_list = is_array($to_user) ? $to_user : [$to_user];
        }
        if (empty($openid_list)) {
            biz_exception("openid为空");
        }

        $handler = $this->easyHandler()->subscribe_message;
        $tpl_msg = [
            'template_id'   => $template['template_id'],
            'page'          => $page ?: $template['page'],
            'data'          => $data
        ];

        /**
         * 3 列表循环发送，记录返回错误消息
         */
        $ret_errors = [];
        foreach ($openid_list as $openid) {
            $tpl_msg['touser'] = $openid;

            $response = $handler->send($tpl_msg);
            if (!isset($response['errcode'])) {
                $ret_errors[] = "{$openid}:未知异常";
                continue;
            }
            if (0 == $response['errcode']) {    //发送成功
                continue;
            }

            if (is_array($response) && !empty($response['errcode'])) {
                $ret_errors[] = $openid . ':' . $response['errcode'] . '-' .$response['errmsg'];
            }
        }

        return $ret_errors;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \EasyUtils\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyUtils\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \EasyUtils\Kernel\exception\BizException
     */
    public function subscribeMessage($data)
    {
        $handler = $this->easyHandler()->subscribe_message;
        $tpl_msg = [
            'template_id'   => $data['template_id'],
            'page'          => $data['url'],
            'data'          => $data['content'],
            'touser'        => $data['openid']
        ];
        $response = $handler->send($tpl_msg);
        if (!isset($response['errcode'])) {
            biz_exception($data['openid'] . ":未知异常");
        } elseif ($response['errcode'] != 0) {
            biz_exception($data['openid'] . ':' . $response['errcode'] . '-' .$response['errmsg']);
        }
        return true;
    }

    /**
     * @return array
     */
    public function getCategory()
    {
        try {
            $handler = $this->easyHandler()->subscribe_message;
            $res = $handler->getCategory();
        } catch (\Exception $e) {
            $res = [
                'errcode' => 1,
                'errmsg' => $e->getMessage()
            ];
        }
        return $res;
    }

    /**
     * @param array $ids
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function getTemplateTitles(array $ids, int $start = 0, int $limit = 30)
    {
        try {
            $handler = $this->easyHandler()->subscribe_message;
            $res = $handler->getTemplateTitles($ids, $start, $limit);
        } catch (\Exception $e) {
            $res = [
                'errcode' => 1,
                'errmsg' => $e->getMessage()
            ];
        }
        return $res;
    }

    /**
     * @param $tid
     * @return array
     */
    public function getTemplateKeywords($tid)
    {
        try {
            $handler = $this->easyHandler()->subscribe_message;
            $res = $handler->getTemplateKeywords($tid);
        } catch (\Exception $e) {
            $res = [
                'errcode' => 1,
                'errmsg' => $e->getMessage()
            ];
        }
        return $res;
    }

    /**
     * @return array
     */
    public function getTemplateList()
    {
        try {
            $handler = $this->easyHandler()->subscribe_message;
            $res = $handler->getTemplates();
        } catch (\Exception $e) {
            $res = [
                'errcode' => 1,
                'errmsg' => $e->getMessage()
            ];
        }
        return $res;
    }

    /**
     * @param string $tid
     * @param array $kidList
     * @param string $sceneDesc
     * @return array
     */
    public function addTemplate(string $tid, array $kidList, string $sceneDesc = null)
    {
        try {
            $handler = $this->easyHandler()->subscribe_message;
            $res = $handler->addTemplate($tid, $kidList, $sceneDesc);
            if (isset($res['errcode']) && $res['errcode'] === 0) {
                $res['data'] = ['priTmplId' => $res['priTmplId']];
            }
        } catch (\Exception $e) {
            $res = [
                'errcode' => 1,
                'errmsg' => $e->getMessage()
            ];
        }
        return $res;
    }

    /**
     * @param string $id
     * @return array
     */
    public function deleteTemplate(string $id)
    {
        try {
            $handler = $this->easyHandler()->subscribe_message;
            $res = $handler->deleteTemplate($id);
        } catch (\Exception $e) {
            $res = [
                'errcode' => 1,
                'errmsg' => $e->getMessage()
            ];
        }
        return $res;
    }

    /**
     * 根据小程序与模板类型名，获取对应的模板消息配置信息
     * @param $aid
     * @param string $tpl_type 模板消息类型，对应管理后台的消息类型，请用如下方式获取：EasyUtils\Kernel\Constant\WxTemplateType
     * @return array
     */
    protected function getTemplates($wxapp_name, $tpl_type)
    {
        $templates = config('wxapp.templates');
        if (empty($templates[$wxapp_name][$tpl_type])) {
            biz_exception('模板类型错误');
        }
        list($template_id, $page) = $templates[$wxapp_name][$tpl_type];
        return compact('template_id', 'page');
    }

    /**
     * 获取小程序订阅消息模板配置信息
     * @param int $tpl_type
     * $param string $wxapp_name
     * @return array
     */
    public static function getMiniTemplate($tpl_type, $wxapp_name, $cacheOpt = ['_cacheTime' => 180])
    {
        if (empty($_GET['skip_cache']) && !empty($cacheOpt['_cacheTime'])) {
            return cache_method(null, __METHOD__, func_get_args(), $cacheOpt);
        }
        $data = [];
        $res = req_wxlib_v2('weixin/wxapp.general/miniTemplate', ['tpl_type' => $tpl_type, 'wxapp_name' => $wxapp_name]);
        if (isset($res['code']) && $res['code'] == 0) {
            $data = [
                'template_id' => $res['data'][0]['priTmplId'],
                'page' => $res['data'][0]['page']
            ];
        }
        return $data;
    }

    /**
     * 判断小程序内容是否有违规内容
     *
     * @param string $content
     * @return array
     */
    public function checkTextContent($content)
    {
        $stream = $this->easyHandler()->content_security->checkText($content);
        return $stream;
    }

    /**
     * 判断图片是否违规
     * @param string $img
     * @return array
     */
    public function checkImg($img)
    {
        $stream = $this->easyHandler()->content_security->checkImage($img);
        return $stream;
    }

    /**
     * @return \EasyUtils\OpenPlatform\Authorizer\MiniProgram\Application | \EasyUtils\MiniProgram\Application
     */
    public function easyHandler()
    {
        $biz_key = $this->wxappName . ($this->aid ? '-' . $this->aid : '');
        return HandlerFactory::easyWechat(HandlerFactory::MINI_PROGRAM, $biz_key);
    }

    protected function redisHandler()
    {
        return redis_handler($this->redisConf, 'template_msg');
    }
}
