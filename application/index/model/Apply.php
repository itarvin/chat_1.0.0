<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\index\model;
use app\index\validate\applyValidate;
use think\Model;
class Apply extends Model{

    protected $auto = ['read' => 0, 'type' => 0];
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime         = 'time';
    protected $updateTime         = false;

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
        // 检测是否已经请求
        $where[] = ['uid', 'eq', $data['uid']];
        $where[] = ['from', 'eq', $data['from']];
        $unique = $this->where($where)->find();
        if($unique){
            return ['code' => 0, 'msg' => '请求已经发起，请耐心等待~', 'data' => ''];
        }
        if($this->allowField(true)->save($data)){
            return ['code' => 1, 'msg' => '请求成功', 'data' => ''];
        }else{
            return ['code' => 0, 'msg' => '网络错误！刷新看看~', 'data' => ''];
        }
    }

     /**
     * 应用场景：读取列表
     * @param string $val    当前数据
     * @return string
     */
    public function lists($where, $len = '15', $sort = 'id desc', $field = '*')
	{
        $res = $this->field($field)->where($where)->order($sort)->limit($len)->select();
        return $res;
    }
}
