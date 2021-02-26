<?php
// 工具函数 - 时间相关
use \EasyUtils\Kernel\Support\Time;

/**
 * 计算日期差
 * @param integer $date1
 * @param integer $date2
 */
function diff_days($date1, $date2) {
    return Time::diffDays($date1, $date2);
}

function get_timestamp($time) {
    return strlen($time) > 10 ? strtotime($time) : $time;
}

/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
function friendly_date($sTime,$type = 'normal',$alt = 'false') {
    return Time::friendlyDate($sTime, $type, $alt);
}

function dateformat($date){
    if(!empty($date))
        return date('Y-m-d',$date);
    else
        return '';
}

function dateTimeFormat($date){
    if(!empty($date))
        return date('Y-m-d H:i',$date);
    else
        return '';
}

function dateFullTimeFormat($date) {
    if (!empty($date))
        return date('Y-m-d H:i:s', $date);
    else
        return '';
}

function dateTformat($date){
   return strtotime(str_replace("T", " ", $date));
}

/* 
*function：计算两个日期相隔多少年，多少月，多少天 
*param string $date1[格式如：2011-11-5] 
*param string $date2[格式如：2012-12-01] 
*return array array('年','月','日'); 
*/  
function diffDate($date1,$date2){  

    $back['bool']=1;
    if(strtotime($date1)>strtotime($date2)){  
        $tmp=$date2;  
        $date2=$date1;  
        $date1=$tmp;
        $back['bool']=0;
    }  
    list($Y1,$m1,$d1)=explode('-',$date1);  
    list($Y2,$m2,$d2)=explode('-',$date2);  
    $Y=$Y2-$Y1;  
    $m=$m2-$m1;  
    $d=$d2-$d1;  
    if($d<0){  
        $d+=(int)date('t',strtotime("-1 month $date2"));  
        $m--;  
    }  
    if($m<0){  
        $m+=12;  
        $y--;  
    }

    $back['year']=$Y;
    $back['month']=$m;
    $back['day']=$d;
    
   
    return $back;  
}  


    /*
        节日信息
    */
 function holiday( $date ){
        
        
        
        $res = array();
        
    
        
        //农历节日
        $holiday = array(
            '01-01'=>'春节',
            '01-15'=>'元宵节',
            '02-02'=>'二月二',
            '05-05'=>'端午节',
            '07-07'=>'七夕节',
            '08-15'=>'中秋节',
            '09-09'=>'重阳节',
            '12-08'=>'腊八节',
            '12-23'=>'小年'
        );
        //公历转农历，并截取月份的日期
        $yangli = date('Y-m-d',$date );
        $nongli=D('Common/Lunar')->getLar($yangli,2);
        $days = date('m-d',$nongli ) ;
  
        
        if( isset( $holiday[ $days ] ) ){
            $res[] = $holiday[ $days ];
        }
        
        ////////////////////////////////////////
        
        $days = date('m-d',$date ) ;
    
        //公历节日
        $holiday = array(
            '01-01'=>'元旦',
            '02-02'=>'世界湿地日(1996)',
            '02-14'=>'情人节',
            '03-03'=>'全国爱耳日',
            '03-08'=>'妇女节(1910)',
            '03-12'=>'植树节(1979)',
            '03-15'=>'国际消费者权益日',
            '03-20'=>'世界睡眠日',
            '03-25'=>'世界气象日',
            '04-01'=>'愚人节',
            '04-07'=>'世界卫生日',
            '05-01'=>'国际劳动节',
            '05-04'=>'中国青年节',
            '05-08'=>'世界红十字日',
            '05-12'=>'国际护士节',
            '05-19'=>'全国助残日',
            '06-01'=>'国际儿童节',
            '06-05'=>'世界环境日',
            '06-22'=>'中国儿童慈善活动日',
            '06-23'=>'国际奥林匹克日',
            '07-01'=>'中国共产党成立(1921)',
            '07-07'=>'中国人民抗日战争纪念日',
            '08-01'=>'中国人民解放军建军(1927)',
            '09-03'=>'抗日战争胜利纪念日(1945)',
            '09-08'=>'国际扫盲日',
            '09-10'=>'教师节',
            '09-16'=>'世界臭氧层保护日',
            '09-18'=>'九一八纪念日',
            '09-27'=>'世界旅游日',
            '09-29'=>'国际聋人节',
            '10-01'=>'国庆节',
            '10-14'=>'世界标准日',
            '10-24'=>'联合国日',
            '12-05'=>'国际志愿人员日',
            '12-29'=>'12.9运动纪念日',
            '12-25'=>'圣诞节'
        );
        
        if( isset( $holiday[ $days ] ) ){
            $res[] = $holiday[ $days ];
        }
        
        return implode( '，', $res );
    
    }



 function birthdayReminder($birthday,$reminder ){

     $preg = '/^(\d{4}|\d{2}|)[- ]?(\d{2})[- ]?(\d{2})$/';
     $Ymd = array();
     
     preg_match($preg, $birthday, $Ymd);
     if (empty($Ymd) ||empty($birthday)) return false;

     
     $birthday = $Ymd[2].'-'.$Ymd[3];
     $time = time();

     for ($i = 1; $i <=  $reminder; $i++){
         
      if (date('m-d', $time) == $birthday) {
         if ($i==1) {
            return "(今天生日)";
          }else {
             return '('.$i."天后生日)";
          }
      }
      $time = $time + 24 * 3600;
     }
     return false;

}

// 时间格式化
function time_format($time=NULL, $format='Y-m-d H:i:s')
{
    $time = $time===NULL ? time() : intval($time);
    return date($format, $time);
}

/**
 * 时间范围比较判断
 * @param int $timeStart
 * @param int $timeEnd
 * @return int|string
 * @author Yung
 * @date 2021/1/22 17:43
 */
function checkTimeRange($timeStart=0, $timeEnd=0)
{
    if($timeStart && $timeEnd){
        if($timeStart>$timeEnd){
            return '开始时间不能大于结束时间';
        }else{
            return 0;
        }
    }else if(($timeStart && !$timeEnd)|| !$timeStart && $timeEnd){
        return '时间不能为空';
    }else{
        return 0;
    }
}


