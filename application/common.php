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

// 应用公共文件
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
