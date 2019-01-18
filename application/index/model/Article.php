<?php
namespace app\index\model;
use think\Model;
/**
 * 应用场景：文章模型类
 * @since   2018/06/19 创建
 * @author  itarvin itarvin@gmail.com
 */
class Article extends Model{
	// 直接使用配置参数名
    protected $connection = 'db_config1';

    /**
     * 应用场景：大图读取时，自动补全路径
     * @param string $val    当前数据
     * @return url
     */
    protected function getPictureAttr($val){
        return bucketFix($val,true);
    }

    /**
     * 应用场景：提交时，删除绝对网址，小图
     * @param string $val    当前数据
     * @return string
     */
    protected function setPictureAttr($val){
        return bucketFix($val,false);
    }

    /**
     * 应用场景：补全内容图片网址
     * @param string $val    当前数据
     * @return html
     */
    protected function getContentAttr($val){
        return bucketSrc($val,true);
    }
    /**
     * 应用场景：删除内容图片网址
     * @param string $val    当前数据
     * @return html
     */
    protected function setContentAttr($val){
        return bucketSrc($val,false);
    }

	/**
     * 应用场景：获取分页列表
     * @return array
     */
	public function getList($where = [], $len = '15', $sort = "id desc", $field = "*")
	{
        $data = $this->field($field)->order($sort)->where($where)->limit($len)->select();
        return $data ? $data : [];
	}

    /**
     * 应用场景：获取分页列表
     * @return array
     */
	public function pages($param = [], $len = '15', $sort = "id desc", $field = "*")
	{
        $where = [];
        if(getval($param,'id')){
            $where[] = ['cateid', 'eq', $param['id']];
        }
        $data = $this->field($field)
        ->order($sort)
        ->where($where)
        ->paginate($len, false, ['query' => $param]);
        foreach ($data as $k => $v) {
            $data[$k]['username'] = (new Admin)->getInfo($v['authorid'], "username");
            $data[$k]['cate_name'] = (new Category)->getInfo($v['cateid'], "name");
        }
        return $data ? $data : [];
	}

    /***
     * 获取指定信息
     * $userid  用户索引
     * $field   输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($userid, $field = "")
    {
        $res = $this->where('id', $userid)
        ->cache('article_'.$userid,7200,'article')
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
