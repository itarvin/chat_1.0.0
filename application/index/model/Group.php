<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\index\model;
use think\Model;
class Group extends Model{

    public function lists($user_id)
	{
        $where[] = ['user_id','eq', $user_id];
        // 查询当前用户有哪些组
        $current = $this->field('groupname,id')->where($where)->select();
        $result = [];
        foreach ($current as $key => $va) {
            $result[$key] = $va;
            $friends = (new GroupUser)->alias('a')->field('b.*')
            ->leftJoin('user b', 'a.u_id = b.id')
            ->where('a.g_id',$va['id'])->select();
            $result[$key]['online'] = $key + 1;
            $result[$key]['list'] = $friends;
        }
        return $result;
	}
}
