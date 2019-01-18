<?php
// +----------------------------------------------------------------------
// | 功能性共用方法
// +----------------------------------------------------------------------

//获取泛解析前缀
function getPan(){
	$pan='';
	$bandPan = ['www'];
	if(request()->rootDomain() == 'qixingtang.com'){
		$pan = request()->subDomain();
		if(in_array($pan, $bandPan)){
			$pan = '';
		}
	}

	return $pan;
}

//生成QQ交谈连接
function qqlink($qq){
	if(request()->isMobile()){
		$qqlink='mqqwpa://im/chat?chat_type=wpa&uin='.$qq.'&version=1&src_type=web&web_src=qq';
	}else{
		$qqlink='http://wpa.qq.com/msgrd?v=3&uin='.$qq.'&site=qq&menu=yes';
	}
	return $qqlink;
}
/**
 * 验证是否登录，并返回用户ID(待完善，可验证的)
 * @return int 0或用户id
 * @throws
 */
function isLogin(){
	$userid = 0;
	$isLogin = session('member_auth');

	if($isLogin){
		$userid = userinfo($isLogin,'id');
		if(!$userid){
			$userid=0;
			session('member_auth',null);
		}
	}else{
		$login_token = cookie('member_token');
        if($login_token){
			$sign = explode('-',$login_token);
			if(count($sign) == 2){
				$current = model('member')->field('id,encrypt')->where('id',$sign[0])->find();
				if(md5(sha1($current['id']).sha1($current['encrypt'])) == $sign[1]){
					session('member_auth',$current['id']);
					$userid = $current['id'];
				}else{
					cookie('member_token',null);
				}
			}
		}
	}
	return $userid;
}

//获取用户积分 --禁用-待删--
function myPoint($userid=''){
	$userid=$userid ? isLogin() : 0;
	$point=0;
	if($userid){
		$point=model('member_point')->where([['userid','=',$userid],['status','=',1]])->sum('point');
	}
	return $point;
}

//地址类型
function addrType(){
	return ['家里','公司','学校','宿舍','老家','其它'];
}

//自动生成日期区间
function betweendate($date,$field='',$spl='~'){
	if(is_string($date)){
		$dt=explode($spl,$date);
		$start 	= trim($dt[0]);
		$end 	= trim($dt[0]);
	}else{
		$start	= getval($date,'start');
		$end	= getval($date,'end');
	}
	$start 	= $start ? date('Y-m-d 00:00:00',strtotime($start)) : '';
	$end 	= $end ? date('Y-m-d 23:59:59',strtotime($end)) : '';
	$retn='';
	if($start && $end){
		$retn = [$field,'BETWEEN',[$start, $end]];
	}elseif($start){
		$retn = [$field,'>=',$start];
	}elseif($end){
		$retn = [$field,'>=',$end];
	}

	return $retn;
}
/*******
 * 读取分类
 * $cid 	numeric 	输出当前分类ID的数据
 * $cid 	module 		当前模型的所有数据
 * $cid 	为空 		所输出空
 * $field 	输出字段名，ID为数字时，有效
*****/
function category($cid="",$field=""){
	$res = model('category')->getCate($cid,$field);
	return $res;
}

//产品列表页URL生成,$pm=参数，$min=要删除的参数
function listUrl($pm=[]){
	$field=['catid','sort','attach','t1','t2','t3','t4'];
	$aprm=request()->param();
	$param=[];
	foreach ($field as $key => $vo) {
		if(isset($aprm[$vo])){
			$param[$vo]=$aprm[$vo];
		}
	}
	foreach ($pm as $key => $vo) {
		if($vo){
			$param[$key] = $vo;
		}else{
			unset($param[$key]);
		}
	}
	return url('mall/index/lists',$param);
}

//桶URL补齐及清除
function bucketFix($url,$addUrl = true){
	$bucketURL=config('cos')['bucketURL'];
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
	$pregRule = "/<img.*?src=[\'|\"](.*?)[\'|\"](.*?)[\/]?>/i";
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

/*******
 * 读取系统共用变量
 * $code 状态码
 * $name 状态类型
 * 当$name为空时，$code为状态类型，并返回该类型的所有状态
*/
function variable($code,$name=''){
	$vari = config('variable');
	if($name === ''){
		$retn = isset($vari[$code]) ? $vari[$code] : '';
	}else{
		$retn = isset($vari[$name][$code]) ? $vari[$name][$code] : '';
	}
	return $retn;
}

/****
 * 共用获取用户资料userinfo(1,'sellid')
 * $userid 	用户ID 或 SellID
 * $field 	输出字段，为空时输出所有，可以为字符串，或数组
*/
function userinfo($userid, $field = ""){
	return model('member')->getUserInfo($userid, $field);
}

/********
 * 自动获取当前用户所属的销售信息
 * $field 输出该信息指定字段
*/
function saleinfo($field=''){
	//泛域名前缀
	$pan = getPan();
	//登录用户ID
	$userid = isLogin();
	$sell_info = '';
	if($userid){
		$user=userinfo($userid);
		if($user['type']>0){
			//自己是销售
			$sell_info = $user;
		}elseif($user['parent_id']){
			//有上级ID
			$sell_info = userinfo($user['parent_id']);
		}
	}elseif($pan){
		//根据专有网址判断
		$sell_info = userinfo($pan);
	}
	//输出某字段
	if($sell_info && $field){
		$sell_info = getval($sell_info, $field, '');
	}
	return $sell_info;
}

// 获取广告位
function getAdvert($key, $type = ''){
    return model('advert')->getadverts($key, $type);
}

/**
 * 创建配置文件
 * @param string $variable 数组名
 * @param array $array 数组
 * @param string $file 文件名
 */
function createfile($data,$file)
{
    // $array 为常量传入文件
    $array = 'return';
    // var_export()解析数组写入文件
    $content = "<?php "."\n"."// 系统参数配置项,无需手动修改"."\n"." $array ".var_export($data, true).";"."\n"."?>";
    // 创建文件存入
    //创建文件夹，多层嵌套的文件夹(递归式)
    $file_path = config('setting_save_path');
    // 判断文件是否存在，未不存在，则创建文件并写入文件。如存在，则覆盖写入重新生成文件
    if(!file_exists($file_path)){
        //0777表示文件夹权限，windows默认已无效，但这里因为用到第三个参数，得填写；true/false表示是否可以递归创建文件夹
        mkdir($file_path,0777,true);
    }
    $result = file_put_contents($file_path.$file,$content);
    return $result;
}

/**
 * 订单物流状态
 * @param int $id 主键
 * @param string $file 文件名
 */
function exchangeStat($id ,$type='manage')
{
	$chinese = [
		'1' => '确认',
		'2' => '配货',
		'3' => '发货',		//需要填写快递单号
		'4' => '到当地',
		'5' => '签收',
		'6' => '拒收',
		'7' => '退货',		//需要填写快递单号
		'8' => '确认退货',
		'9' => '取消',
		'10' => '完成',
		'11' => '已完成',

		//前台自定义操作
		'comment' 	=> '订单评价',
		'aftersale'   => '申请售后',
		'hurryup' 	=> '催促发货',
		'checkpay'	=> '查帐',
	];
	$actions = [
		//后台及销售
		'manage'=>[
			'0'     => ['9','1','2','3'],
			'1'     => ['9','2','3'],
			'2'     => ['9','3'],
			'3'     => ['9','4','5','6'],
			'4'     => ['5','6','9'],
			'5'     => ['7','8','10'],
			'6'     => ['7','8','9'],
			'7'     => ['9','8'],
			'8'     => ['9'],
			'9'     => ['1','2','3'],
			'10'    => ['11']
		],
		//普通用户
		'user' =>[
			'0'     => ['hurryup'],
			'1'     => ['hurryup'],
			'2'     => ['hurryup'],
			'3'     => ['5','6'],
			'4'     => ['5','6'],
			'5'     => ['7','comment'],		//,'aftersale'
			'6'     => [],
			'7'     => [],
			'8'     => [],
			'9'     => [],
			'10'    => []
		]
	];

	$stat = $actions[$type];

	if(is_numeric($id)){
		$status = $stat[$id];
		$resu = [];
		foreach ($status as $key => $value) {
			$resu[$value] = $chinese[$value];
		}
		return $resu;
	}
	return [];
}

/**
 * 删除掉匹配出的图片链接
 * @param int $order 状态值
 * @return array 按钮
 */
function checkImg($url = [], $deep = false){
	$newimg = [];
	$suffix = config('cos')['bucketURL'];
	foreach ($url as $key => $path) {
		if($deep){
			// 正则出所有的路径
			$pregRule = "/<img.*?[src=][\'|\"](.*?)[\'|\"](.*?)[\/]?>/i";
			preg_match_all($pregRule,$path,$matchs);
			foreach ($matchs[1] as $key => $value) {
				$newimg[] = str_replace($suffix, '', $value);
			}
		}else{
			$newimg[] = str_replace($suffix, '', $path);
		}
	}
	model('images')->del($newimg);
}

//长网址转百度短网址，新网址接口
function shortURL($url){
	$api_url = 'http://api.weibo.com/2/short_url/shorten.json?source=31641035&url_long='.urlencode($url);
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $api_url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$file_contents = curl_exec($ch);
	curl_close($ch);
	$html_arr = json_decode($file_contents,true);
	if($html_arr['urls'][0]['url_short']!=""){
		$dwz = $html_arr['urls'][0]['url_short'];
	}else{
		$dwz = $url;
	}
	return $dwz;
}
