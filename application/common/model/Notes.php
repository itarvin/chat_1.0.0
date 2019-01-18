<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\common\model;
use app\common\validate\Notes as notesValidate;
use app\common\service\PubliclyWhere;
class Notes extends Common{

	protected $insert = ['iread' => 0, 'ilike' => 0];
	protected $autoWriteTimestamp = 'datetime';
	protected $createTime         = 'add_time';
    protected $json = ['relative'];

	/**
     * 应用场景：获取图
     * @param string $val    当前数据
     * @return url
     */
	protected function getThumbAttr($val){
        return bucketFix($val);
    }

	/**
     * 应用场景：获取作者
     * @param string $val    当前数据
     * @return string
     */
	protected function getAuthorAttr($val, $data){
        if(!$val){
            $val = saleinfo('nickname');
        }
        return $val;
    }

	/**
     * 应用场景：设置用户ID
     * @param string $val    当前数据
     * @return string
     */
	protected function setUseridAttr($val){
        return $val ? $val : 0;
	}

	/**
     * 应用场景：获取分类
     * @param string $val    当前数据
     * @return string
     */
    protected function getClassAttr($catid){
        return category($catid);
    }

	/**
     * 应用场景：设置图
     * @param string $val    当前数据
     * @return string
     */
	protected function setThumbAttr($val){
        return bucketFix($val,true);
    }

	/**
     * 应用场景：补全内容图片网址
     * @param string $val    当前数据
     * @return string
     */
    protected function getContentAttr($val){
        return bucketSrc($val);
    }

	/**
     * 应用场景：提交时，删除内容图片网址
     * @param string $val    当前数据
     * @return string
     */
    protected function setContentAttr($val){
        return bucketSrc($val,false);
    }

	/**
     * 应用场景：截取时间
     * @param string $val    当前数据
     * @return string
     */
    protected function getAddTimeAttr($val){
        return date('Y年m月d日', strtotime($val));
    }

	//相关产品-读取
    protected function setRelativeAttr($val){
        $res = array_values($val);
		return $res;
    }

    //相关产品-读取
    protected function getRelativeAttr($val){
        $retn = [];
        if(is_array($val) && count($val)>0){
            $retn = model('goods')->lists([['id','IN',$val]]);
        }
		return $retn;
    }

    //虚拟字段：相关产品数据
    public function getUrlAttr($vo, $data){
        return url('mall/notes/show',['id'=>$data['id']]);
    }

	/**
     * 应用场景：读取内容
     * @param string $val    当前数据
     * @return string
     */
    public function getNote($id){
        $res = $this->where([['id','=',$id]])->append(['class'])->find();
    }

	/**
     * 应用场景：读取列表
     * @param string $val    当前数据
     * @return string
     */
    public function lists($param, $len = '15', $sort = 'id desc', $field = '*')
	{
        $param['status'] = getval($param,'status',1);
        $res = $this->build($param, $len, $sort, $field)->select();
        return $res;
    }

	/**
     * 获取所有信息
     * @param $param 查询条件
     * @param $field 输出字段
     * @param $sort  排序
     * @param $len   输出条数
     * @return $data 数据集
     */
    public function pages($where=[], $len=15, $sort='', $field='*'){
        $user = saleinfo('id');
        if($user){
            $where[] = ['userid','in',[0,$user]];
        }else{
            $where[] = ['userid','=',0];
        }
		$data = $this->build($where, '', $sort, $field)->paginate($len, false, ['query' => $where]);
        return $data;
    }

    /**
     * 应用场景：新增，修改数据时的数据验证与处理
     * @param string $data    所有数据
     * @return array
     */
    public function store($data)
    {
		$where = [];
        $validate = new notesValidate;
        if (!$validate->check($data)) {
            // 验证数据失败
            return apiReturn('',0,$validate->getError());
        }
		checkImg([$data['content']], true);
		checkImg([$data['thumb']]);
		if($data['id'] != ''){
			$where[] = ['id', 'eq', $data['id']];
            $this->dealCache('notes', $data['id']);
			if($this->allowField(true)->save($data,$where)){
	            return apiReturn('',1,'更新成功');
	        }
		}else {
			if($notes = self::create($data)){
	            return apiReturn($notes['id'],1,'发布成功！');
	        }
		}
		return apiReturn('',0,'网络错误，请稍后再试！');
    }

    /**
     * 删除笔记
     * @param int id
     * @return json
     */
    public function del($id)
    {
        // 删除数据
        if($this->where('id',$id)->delete()){
			$this->dealCache('notes', $id);
            return apiReturn('',1,'删除成功！');
        }else{
            return apiReturn('',0,'网络错误，请稍后再试！');
        }
    }

	/***
     * 获取指定信息
     * $userid   id索引
     输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($id, $field = "")
    {
        $res = $this->where('id', $id)->cache('notes_'.$id,7200,'notes')->find();
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
