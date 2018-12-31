<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\index\model;
use think\Model;
class Friend extends Model{


	public function lists($user_id)
	{
		$where[] = ['a.u_id', 'eq', $user_id];
		$data = $this->alias('a')->field('b.*')
		->where($where)
		->leftJoin('user b', 'a.f_id = b.id')
		->select();
		return $data;
	}
}
