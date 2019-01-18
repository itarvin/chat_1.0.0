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
        $groupUserMod = new GroupUser;
        $groups = [];
        // 统计
        $group = $groupUserMod->where('u_id', $user_id)->select();
        // foreach ($group as $key => $value) {
        //     $groups[] = $this->field('groupname,id')->where('id', $value['g_id'])->find()->toArray();
        // }
        // 查询当前用户有哪些组
        $cur = $this->field('groupname,id')->where($where)->select()->toArray();
        // var_dump($cur);die;
        // $current = array_merge($groups,$cur);
        $result = [];
        foreach ($cur as $key => $va) {
            $result[$key] = $va;
            $friends = $groupUserMod->alias('a')->field('b.*')
            ->leftJoin('user b', 'a.u_id = b.id')
            ->where('a.g_id',$va['id'])->select()->toArray();
            $result[$key]['list'] = $friends;
        }
        return $result;
	}
}
