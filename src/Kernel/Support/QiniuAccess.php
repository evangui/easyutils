<?php

namespace EasyUtils\Kernel\Support;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuAccess
{
    protected $bucket;
    protected $accessKey;
    protected $secretKey;
    /** @var Auth $auth */
    protected $auth;

    public function __construct()
    {

        $this->bucket = config('qiniu.bucket');
        $this->accessKey = config('qiniu.accessKey');
        $this->secretKey = config('qiniu.secrectKey');
        $this->auth = new Auth($this->accessKey, $this->secretKey);
    }

    /**
     * 上传到七牛云服务器
     * @param mixed $local_file 本地文件
     * @return bool|string
     */
    public function uploadToQiniu($local_file)
    {
        //上传到七牛
        $ext = pathinfo($local_file, PATHINFO_EXTENSION);
        $key = $this->genQiniuKey($ext);

        if ($this->qiniuUpload($local_file, $key)) {
            $host = config('qiniu.host.online');
            if (in_array(strtolower($ext), ['jpg', 'png', 'jpeg'])) {// 如果上传的是图片，返回图片域名
                return $host[2] . '/' . $key;
            } else {// 否则，返回音频域名
                return $host[1] . '/' . $key;
            }
        } else {
            return false;
        }
    }

    /**
     * 上传文件到七牛
     */
    public function qiniuUpload($file_path, $key)
    {
        $token = $this->auth->uploadToken($this->bucket, $key);

        $upload_mgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $upload_mgr->putFile($token, $key, $file_path);

        if ($err !== null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取生成的文件相对路径
     * @param string $ext 文件后缀
     * @return string
     */
    public function genQiniuKey($ext)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        switch (strtolower($ext)) {
            case 'mp3':
                $prefix = 'audio/';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
                $prefix = 'image/';
                break;
            default:
                $prefix = 'other/';
        }

        $rand = rand(0, 25);
        return $prefix . time() . '_' . $chars[$rand] . '.' . $ext;
    }
}
