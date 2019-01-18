<?php
namespace app\index\model;
use think\Model;
/**
 * 应用场景：配置模型类
 * @since   2018/06/19 创建
 * @author  itarvin itarvin@gmail.com
 */
class Admin extends Model{
    // 直接使用配置参数名
    protected $connection = 'db_config1';

    /***
     * 获取指定管理员信息
     * $userid      用户索引
     * $field       输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($userid, $field = "")
    {
        $res = $this->where('id', $userid)
        ->hidden(['password'])
        ->cache('admin_'.$userid,7200,'admin')
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
