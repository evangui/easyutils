<?php

namespace EasyUtils\Kernel\Support;

class ArticleUtil
{

    private static $_config = [
        "pathFormat" => "/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}", /* 上传保存路径,可以自定义保存路径和文件名格式 */
        "maxSize" => 102400000,/* 上传大小限制，单位B */
        "allowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"],/* 抓取图片格式显示 */
        "oriName" => "remote.png"
    ];

    public static function isWhiteList($url){
        $whiteList = ["bookgo.com.cn",'webplus.zuel.edu.cn'];
        $isOk = false;

        foreach ($whiteList as $item) {

            if( strpos($url, $item) > 0 ) {
                $isOk = true;
                break;
            }
        }
        return $isOk;
    }

    /**
     * 判断是否为https
     * @return bool 是https返回true;否则返回false
     */
    public static function isHttps() {
        if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
            return true;
        } elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }else{
            return false;
        }
    }

    public static function replaceImg($content)
    {
//        $content = preg_replace('/data-src=[\"|\']?(.*?)[\"|\']/i','',$content);  //过滤data-src
        $preg = '/<img.*? src=[\"|\']?(.*?)[\"|\']/i';
        preg_match_all($preg, $content,$match);

        $preg2 = '/url\(.*?(http.[^\)]+?)[\"|\']?(&quot;)?\)/i';
        preg_match_all($preg2, $content,$match2);
        ve($match2);
        if (isset($match[1]) && isset($match2[1])) {
            $match_all = array_merge($match[1], $match2[1]);
        } else if (isset($match[1])) {
            $match_all = $match[1];
        } else if (isset($match[2])) {
            $match_all = $match[2];
        } else {
            $match_all = [];
        }
        if( count($match_all) > 0 ) {
            $qiniu = new QiniuAccess();
            foreach ($match_all as $imgUrl) {
                $oldimgUrl = $imgUrl;
                if( strpos($imgUrl, "bookgo.com.cn") > 0 ) {
                    continue;
                }
                // 非http开头 追加网络地址
                if(strpos($imgUrl, "http") !== 0 ) {
                    $imgUrl = 'http://'.$_SERVER['HTTP_HOST'].'/'.$imgUrl;
                }
                //白名单地址可不做图片转换
                if(self::isWhiteList($imgUrl)){
                    continue;
                }
                try {
                    //code...
                    $filename = self::download($imgUrl);
                    if ($filename) {
                        $local_file = env('ROOT_PATH'). 'public/upload/' . date("Ymd") . "/" . $filename;
                        $new_file = $qiniu->uploadToQiniu($local_file);
                        $content = str_replace($oldimgUrl, $new_file, $content);
                    }
                } catch (\Exception $e) {
                    //Log::error(__METHOD__."上传图片异常".$imgUrl);
                    log_trace("图片地址".$imgUrl."无法访问，请重新上传图片");
//                    throw new \Exception("图片地址".$imgUrl."无法访问，请重新上传图片");
                }

            }
        }

        return $content;
    }

    public static function file_exists($url)
    {
        if(file_get_contents($url,0,null,0,1)){
            return 1;

        }
        return 0;
    }

    public static function download($url)
    {
        $path = env('ROOT_PATH'). 'public/upload/' . date("Ymd") . "/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($url, PATHINFO_BASENAME);
        if( strpos($filename, "?") !== false ){
            $filename = substr($filename,0, strpos($filename, "?"));
        }
        if (FileUtil::createDirectory($path)) {
            $resource = fopen($path . $filename, 'a');
            fwrite($resource, $file);
            fclose($resource);
            $file_type = self::getImagetype($path . $filename);
            $new_file_name = rand(100,999) . time() . rand(100,999) . "." . $file_type;
            rename($path . $filename,$path . $new_file_name);
            return $new_file_name;
        }

        return false;
    }

    public static function getImagetype($filename)
    {
        $file = fopen($filename, 'rb');
        $bin = fread($file, 2); //只读2字节
        fclose($file);
        $strInfo = @unpack('C2chars', $bin);
        $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);

        switch ($typeCode) {
            case 255216:
                $fileType = 'jpg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 6677:
                $fileType = 'bmp';
                break;
            case 13780:
                $fileType = 'png';
                break;
            default:
                $fileType = 'jpg';
        }
        return $fileType;
    }
}
