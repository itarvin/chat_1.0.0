<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\common\model;
use app\common\validate\Category as cateVali;
use app\admin\service\MenuOrder;
/**
 * 产品分类模型
 * @package app\admin\model
 */
class Category extends Common{

    /**
     * 获取分类
     * $catid 分类ID，为空时输出列表
    */
    public function getCate($catid='', $field=""){
        $res = $this->cache(true)->select();
        $res = changekey($res,'id');
        $retn = [];
        if(is_numeric($catid)){
            if($catid && isset($res[$catid])){
                $retn = $res[$catid];
                if($field && isset($retn[$field])){
                    $retn = $retn[$field];
                }
            }
        }elseif(is_string($catid) && $catid){
            foreach ($res as $key => $vo) {
                if($vo['module'] == $catid){
                    $retn[] = $vo;
                }
            }
        }else{
            $retn = '';
        }
        return $retn;
    }

    /**
     * 获取所有信息
     * @param $where 查询条件
     * @param $field 输出字段
     * @param $sort  排序
     * @param $len   输出条数
     * @return $data 数据集
     */
    public function lists($where = [], $len = '15', $sort = "id desc", $field = "*")
    {
        $data =$this->build($where, $len, $sort, $field)->select();
        return $data ? $data : [];
    }

    /**
     * 应用场景：新增，修改数据时的数据验证与处理
     * @param string $data    所有数据
     * @return array
     */
    public function store($data)
    {
        $validate = new cateVali;
        if (!$validate->check($data)) {
            // 验证数据失败
            return apiReturn('',0,$validate->getError());
        }
        $where[] = ['name','eq',$data['name']];
        $where[] = ['module','eq',$data['module']];
        $condition = [];
		if(isset($data['id'])){
            // 处理唯一
            $unique = $this->field('id')->where($where)->find();
            if($unique && $unique['id'] != $data['id']){
                return apiReturn('',0,'分类名称已经存在！');
            }
            $condition[] = ['id', 'eq', $data['id']];
            $this->dealCache('category',$data['id']);
		}else {
            // 处理唯一
            $unique = $this->where($where)->find();
            if($unique){
                return apiReturn('',0,'分类名称已经存在！');
            }
		}
        if($this->allowField(true)->save($data,$condition)){
            return apiReturn('',1,'操作成功');
        }else{
            return apiReturn('',0,'网络错误，请稍后再试！');
        }
    }

    /**
     * 删除产品分类
     * @param int id
     * @return json
     */
    public function del($id)
    {
        // 清缓存
        $this->dealCache('category',$id);
        // 删除数据
        if($this->where('id',$id)->delete()){
            return apiReturn('',1,'删除成功！');
        }else{
            return apiReturn('',0,'网络错误，请稍后再试！');
        }
    }

    /**
     * 获取对应模块树形数据
     * @param $module 模块
     * @return function
     */
    public function getTree($module)
    {
        $data = $this->where('module', $module)->select()->toArray();
    	return MenuOrder::_reSort($data);
    }

    /***
     * 获取指定信息
     * $userid   id索引
     输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($id, $field = "")
    {
        $res = $this->where('id', $id)->cache('category_'.$id,7200,'category')->find();
        if($res){
            if(is_array($field)){
                $retn = [];
                foreach ($field as $key => $value) {
                    $retn[$value] = getval($res, $value);
                }
                $res = $retn;
            }elseif($field && is_string($field)){
                $res = getval($res, $field);
            }
        }
        return $res;
    }
}
