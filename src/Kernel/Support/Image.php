<?php
namespace EasyUtils\Kernel\Support;

class Image
{
    /**
     * 对图片进行base64解码
     * @param $str
     * @return mixed
     */
    public static function  base64Encode ($image_file, $add_data_mine=true, $split=false)
    {
        if (false !== strpos($image_file, 'http')) {
            $image_info   = ['mime' => 'image/jpeg'];
            $image_data   = file_get_contents($image_file);
        } else {
            $image_info   = getimagesize($image_file);
            $image_data   = fread(fopen($image_file, 'r'), filesize($image_file));
        }
        $encoded      = $split ? chunk_split(base64_encode($image_data)) : base64_encode($image_data);;
        $base64_image = $add_data_mine ? 'data:' . $image_info['mime'] . ';base64,' . $encoded : $encoded;
        return $base64_image;
    }

    /**
     * 对图片进行base64解码
     * @param $str
     * @return mixed
     */
    public static function  base64Decode ($encoded)
    {
        $encoded = strstr($encoded, ';base64,');
        return base64_decode(str_replace(';base64,', '', $encoded));
    }

    /**
     * @param string $tmpname  原文件路径
     * @param int $size
     * @param string $save_path 新文件路径
     * @param int $maxisheight  $size是否为最长值为高
     * @return bool
     */
    public static function resize($tmpname, $size, $save_path, $maxisheight = 0 )
    {
        $gis  = getimagesize($tmpname);
        switch($gis['mime']) {
            case "image/gif": $imorig = imagecreatefromgif($tmpname); break;
            case "image/jpeg": $imorig = imagecreatefromjpeg($tmpname);break;
            case "image/png": $imorig = imagecreatefrompng($tmpname); break;
            case "image/x-ms-bmp": $imorig = imagecreatefrombmp($tmpname); break;
            default:  $imorig = imagecreatefromjpeg($tmpname);
        }
        $x = imagesx($imorig);
        $y = imagesy($imorig);
        $woh = (!$maxisheight)? $gis[0] : $gis[1] ;
        if($woh <= $size) {
            $aw = $x;
            $ah = $y;
        } else {
            if(!$maxisheight){
                $aw = $size;
                $ah = $size * $y / $x;
            } else {
                $aw = $size * $x / $y;
                $ah = $size;
            }
        }
        $im = imagecreatetruecolor($aw,$ah);
        if (imagecopyresampled($im,$imorig , 0,0,0,0,$aw,$ah,$x,$y))
            if (imagejpeg($im, $save_path))
                return true;
            else
                return false;
    }//img_resize
}



if (!function_exists('imagecreatefrombmp')) {
    function imagecreatefrombmp( $filename )
    {
        $file = fopen( $filename, "rb" );
        $read = fread( $file, 10 );
        while( !feof( $file ) && $read != "" )
        {
            $read .= fread( $file, 1024 );
        }
        $temp = unpack( "H*", $read );
        $hex = $temp[1];
        $header = substr( $hex, 0, 104 );
        $body = str_split( substr( $hex, 108 ), 6 );
        if( substr( $header, 0, 4 ) == "424d" )
        {
            $header = substr( $header, 4 );
            // Remove some stuff?
            $header = substr( $header, 32 );
            // Get the width
            $width = hexdec( substr( $header, 0, 2 ) );
            // Remove some stuff?
            $header = substr( $header, 8 );
            // Get the height
            $height = hexdec( substr( $header, 0, 2 ) );
            unset( $header );
        }
        $x = 0;
        $y = 1;
        $image = imagecreatetruecolor( $width, $height );
        foreach( $body as $rgb )
        {
            $r = hexdec( substr( $rgb, 4, 2 ) );
            $g = hexdec( substr( $rgb, 2, 2 ) );
            $b = hexdec( substr( $rgb, 0, 2 ) );
            $color = imagecolorallocate( $image, $r, $g, $b );
            imagesetpixel( $image, $x, $height-$y, $color );
            $x++;
            if( $x >= $width )
            {
                $x = 0;
                $y++;
            }
        }
        return $image;
    }
}