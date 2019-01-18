<?php
namespace app\index\model;
use think\Model;
/**
 * 应用场景：分类模型类
 * @since   2018/06/21 创建
 * @author  itarvin itarvin@gmail.com
 */
class Category extends Model{
	// 直接使用配置参数名
    protected $connection = 'db_config1';

     /**
      * 应用场景：获取分页列表
      * @return array
      */
 	public function getList($where = [], $len = '15', $sort = "id desc", $field = "*")
 	{
         $data = $this->field($field)->order($sort)->where($where)->limit($len)->select();
         return $data ? $data : [];
 	}

    /***
     * 获取指定信息
     * $cateid  用户索引
     * $field   输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($cateid, $field = "")
    {
        $res = $this->where('id', $cateid)
        ->cache('category_'.$cateid,7200,'category')
        ->find();
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
