<?php
namespace app\index\model;
use think\Model;
use app\index\model\User;
/**
 * 应用场景：聊天记录模型类
 * @since   2018/06/19 创建
 * @author  itarvin itarvin@gmail.com
 */
class Chat extends Model{

    protected $insert = ['type' => 0, 'isread' => 0];
	protected $autoWriteTimestamp = 'datetime';
	protected $createTime = 'addtime';
    protected $updateTime = false;

    /**
     * 应用场景：新增|更新储存
     * @return json
     */
	public function store($data)
	{
        $data['fromname']= (new User)->getInfo($data['fromid'],'username');
        $data['toname']= (new User)->getInfo($data['toid'],'username');
        if($this->allowField(true)->save($data)) {
            return ['code' => 200,'msg'=> '嗯呢！'];
        }else{
            return ['code' => 400,'msg'=> '失败了'];
        }
	}


    public function pages($data)
	{
        $data = $this->alias('a')
        ->field('a.fromid as id,a.fromname as username,a.content,a.addtime,b.avatar')
        ->leftJoin('user b', 'a.fromid = b.id')
        ->where('a.fromid|a.toid',$data['user_id'])
        ->where('a.fromid|a.toid',$data['id'])
        ->order('a.id desc')
        ->select();
        // ->paginate('30');
        return $data;
	}
}
