<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\index\model;
use think\Model;
class User extends Model{

    /***
     * 获取指定管理员信息
     * $userid      用户索引
     * $field       输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($userid, $field = "")
    {
        $res = $this->where('id', $userid)->find();
        if($res){
            if(is_array($field)){
                $retn = [];
                foreach ($field as $key => $value) {
                    $retn[$value] = isset($res[$value]) ?$res[$value] : [] ;
                }
                $res = $retn;
            }elseif($field && is_string($field)){
                $res = isset($res[$field]) ? $res[$field] : '';
            }
        }
        return $res;
    }


    /***
     * 获取指定管理员信息
     * $userid      用户索引
     * $field       输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function store($data)
    {
        if(isset($data['user_id'])){
            if($this->allowField(true)->save($data,['id' => $data['user_id']])){
                return ['code' => 1, 'msg' => '', 'data' => ''];
            }else{
                return ['code' => 0, 'msg' => '', 'data' => ''];
            }
        }
    }
}
