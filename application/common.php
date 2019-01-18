<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use app\index\model\Config;
// 应用公共文件

//计算过去的时间
function time_ago($posttime){
    $nowtimes = strtotime('now');
    $posttime = strtotime($posttime);
    $diff = ($nowtimes-$posttime)/60;   //分钟

    if($diff < 1){
        return '刚刚';
    }elseif($diff < 60){
        return round($diff,0).'分钟前';
    }elseif($diff <= 60*24){
        return round($diff/60,0).'小时前';
    }elseif($diff/60/24 < 7){
        return round($diff/60/24,0).'天前';
    }else{
        return date('Y年m月d日',$posttime);
    }
    return $diff;
}
/********
 * API返回
 * $data        返回的数据
 * $code        操作是否成功，0/1  [status,code]
 * $msg         返回消息
 * $url         返回URL
*/
function apiReturn($data='', $code = 1, $msg = '', $url = ''){
    if(gettype($data) == 'object'){
        $data = $data->toArray();
    }
    if(is_array($code)){
        $statcode = $code[1];
        $code = $code[0];
    }
    $retn=[
        'code'      =>  $code,
        'data'      =>  $data,
        'msg'       =>  $msg,
        'url'       =>  $url,
    ];
    if(isset($statcode)){
        $retn['stat_code'] = $statcode;
    }
    if(isset($data['data'])){
        unset($retn['data']);
        $retn = array_merge($retn,$data);
    }
    return $retn;
}

/***
 * 检查数组中是否有该参数（替换上面）
 * @param $array    array/object    数组或对象
 * @param $key      array/string    变量名
 * @param $default  string          默认值
*/
function getval($array,$key,$default=''){
    $retn = [];
    if(isset($array)){
        if(is_array($array) || is_object($array)){
            if(is_array($key)){
                foreach ($key as $k => $vo) {
                    if(is_array($array)){
                        $retn[$vo] = array_key_exists($vo,$array) ? $array[$vo] : $default;
                    }elseif(is_object($array)){
                        $retn[$vo] = isset($array->$vo) ? $array->$vo : $default;
                    }else{
                       $retn[$vo] = $default;
                    }
                }
            }else{
                if(is_array($array)){
                    $retn = array_key_exists($key,$array) ? $array[$key] : $default;
                }elseif(is_object($array)){
                    $retn = isset($array->$key) ? $array->$key : $default;
                }else{
                   $retn = $default;
                }
            }
        }
    }else{
        $retn = $default;
    }
    return $retn;
}


/**
 * 应用场景：获取配置项
 * @return string
 */
function getconf($key)
{
    if($key){
        $result = (new Config)->field('content')->where('name',$key)->find();
    }
    return $result['content'] ? $result['content'] : "不是每一片云彩都有雨！";
}


//桶URL补齐及清除
function bucketFix($url,$addUrl = true){
	$bucketURL = config('cos')['bucketURL'];
	if($url){
		if($addUrl){
			$url = is_numeric(strpos($url,$bucketURL)) ? $url : $bucketURL.$url;
		}else{
			$url = str_replace($bucketURL, "", $url);
		}
	}
	return $url;
}

//桶URL补齐内容图片src版
function bucketSrc($content = "",$addUrl = true){
	$suffix = config('cos')['bucketURL'];
	$pregRule = "/<img.*?[src=][\'|\"](.*?)[\'|\"](.*?)[\/]?>/i";
	preg_match_all($pregRule,$content,$matchs);
	foreach ($matchs[0] as $key => $vo) {
		if($addUrl){
			$newimg = str_replace($suffix, '', $matchs[1][$key]);
        	preg_match('/^http(s*):\/\//i',$matchs[1][$key],$mahurl);
            $newimg = count($mahurl) ? $matchs[1][$key] : $suffix.$matchs[1][$key];
		}else{
			$newimg = str_replace($suffix, '', $matchs[1][$key]);
		}
		$content = str_replace($matchs[0][$key], '<img src="'.$newimg.'" '.$matchs[2][$key].' />', $content);
    }
    return $content;
}


/**
 * 随机字符，加密盐
 * @param int    $length  长度
 * @param string $type    类型
 * @param int    $convert 转换大小写 1大写 0小写
 * @return string
 */
function random($length = 10, $type = 'all', $convert = ''){
    $config = [
        'number' => '12345678901234567890123456',
        'letter' => 'abcdefghijklmnopqrstuvwxyz',
    ];

    if (isset($config[$type])) {
        $string = $config[$type];
    }else{
        $string = $config['number'].$config['letter'].$config['number'].strtoupper($config['letter']);
    }

    $code   = [];
    $strlen = strlen($string) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code[] = $string{mt_rand(0, $strlen)};
    }
    shuffle($code);
    $code=join('',$code);
    if (is_numeric($convert)) {
        $code = ($convert>0) ? strtoupper($code) : strtolower($code);
    }
    return $code;
}

/**
 * 应用场景：邮件模板
 * @param $data 发送数据
 * @param $url  回调地址
 * @param $title  邮件标题
 */
function sendMail($data,$url,$title,$notice = false,$type = '')
{
    if($notice == true){
        $content = '<div style="background:#fff;border:1px solid #ccc;margin:2%;padding:0 30px"><div style="line-height:40px;height:40px">&nbsp;</div><p style="margin:0;padding:0;font-size:14px;line-height:30px;color:#333;font-family:arial,sans-serif;font-weight:bold">亲爱的'.$data["username"].'：</p><div style="line-height:20px;height:20px">&nbsp;</div><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif">您好！感谢您使用itarvin平台，您的第三方服务'.$type.'登录绑定已变更！此邮件由系统自动发出，仅做通知！勿作回复！</p><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif"><b style="font-size:18px;color:#f90"></b></p><div style="line-height:80px;height:80px">&nbsp;</div><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif">itarvin</p><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif">'.date('Y年m月d日').'</p><div style="line-height:20px;height:20px">&nbsp;</div></div>';
    }else{
        $content = '<div style="background:#fff;border:1px solid #ccc;margin:2%;padding:0 30px"><div style="line-height:40px;height:40px">&nbsp;</div><p style="margin:0;padding:0;font-size:14px;line-height:30px;color:#333;font-family:arial,sans-serif;font-weight:bold">亲爱的'.$data["email"].'：</p><div style="line-height:20px;height:20px">&nbsp;</div><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif">您好！感谢您使用itarvin平台，您正在进行邮箱验证，本次请求的链接为：</p><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif"><b style="font-size:18px;color:#f90"><a href='.$url.' target="_blank">查看链接</a></b><span style="margin:0;padding:0;margin-left:10px;line-height:30px;font-size:14px;color:#979797;font-family:"宋体",arial,sans-serif">(为了保障您帐号的安全性，请在1小时内完成验证。)</span></p><div style="line-height:80px;height:80px">&nbsp;</div><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif">itarvin</p><p style="margin:0;padding:0;line-height:30px;font-size:14px;color:#333;font-family:"宋体",arial,sans-serif">'.date('Y年m月d日').'</p><div style="line-height:20px;height:20px">&nbsp;</div></div>';
    }
    \phpmailer\Email::send($data['email'],$title, $content);
}
