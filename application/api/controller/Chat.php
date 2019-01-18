<?php
namespace app\api\controller;
use app\index\model\Chat as chatMod;
use app\index\model\GroupUser;
use app\index\model\Friend;
use app\index\model\Apply;
use app\index\model\User;
use think\facade\Request;
use think\Controller;

class Chat extends Controller{

    protected $param;
    protected function initialize(){
        $this->param = $this->request->param();
    }

    /**
     *文本消息的数据持久化
     */
    public function save_message()
    {
        if($this->request->isAjax()){
            $chatMod = new chatMod;
            $param = $this->param;
            return $chatMod->store($param);
        }
    }

    /**
     *文本消息的数据持久化
     */
    public function get_message()
    {
        if($this->request->isAjax()){
            $userMod = new User;
            $param = $this->param;
            if(isset($param['id']) && isset($param['type']) && $param['type'] == 'friend' && isset($param['user_id'])){
                // 获取聊天信息
                $data = (new chatMod)->pages($param);
                return ['code' => 200, 'msg' => '', 'data' => $data];
            }else{
                return ['code' => 400, 'msg' => '', 'data' => ''];
            }
        }
    }


    /**
     *文本消息的数据持久化
     */
    public function inquiry()
    {
        if($this->request->isPost() && $this->request->isAjax()){
            $userMod = new User;
            $param = $this->param;
            // 获取选择的ID的资料
            $data = $userMod->getInfo($param['number'], ['id','avatar','username','online']);
            if($data){
                return apiReturn($data,1,'获取成功~');
            }else{
                return apiReturn('',0,'此人暂不存在~');
            }
        }
    }


    /**
     *文本消息的数据持久化
     */
    public function set_apply()
    {
        if($this->request->isPost() && $this->request->isAjax()){
            $applyMod = new Apply;
            $param = $this->param;
            $param['content'] = '申请添加你为好友';
            $result = $applyMod->store($param);
            return $result;
        }
    }

    /**
     *文本消息的数据持久化
     */
    public function get_msg()
    {
        if($this->request->isPost() && $this->request->isAjax()){
            $applyMod = new Apply;
            $param = $this->param;
            $where[] = ['uid', 'eq', $param['user_id']];
            $where[] = ['read', 'eq', 0];
            if(getval($param,'count')){
                $result = $applyMod->lists($where, '0', 'id desc', '*');
                return apiReturn(count($result),1,'获取成功~');
            }else{
                $pages = getval($param, 'page') ? $param['page'] : 1;
                $result = $applyMod->where($where)->limit(3)->page($pages)->select();
                $data = [];
                foreach ($result as $k => $v) {
                    $res = $v;
                    $res['user'] = (new User)->getInfo($v['from'], ['id', 'avatar', 'username', 'sign']);
                    $data[] = $res;
                }
                return json(['code' => 0, 'pages' => $pages, 'data' => $data]);
            }
        }
    }

    /**
     *文本消息的数据持久化
     */
    public function msg_read($type, $user_id)
    {
        if($this->request->isPost() && $this->request->isAjax()){
            $applyMod = new Apply;
            $where[] = ['uid', 'eq', $user_id];
            $result = $applyMod->lists($where, '0', 'id desc', '*');
            $list = [];
            foreach ($result as $key => $value) {
                $list[] = ['id' => $value['id'], 'read' => $type];
            }
            $applyMod->saveAll($list);
        }
    }


    public function agree_friend()
    {
        $param = $this->param;
        $applyMod = new Apply;
        $where[] = ['from', 'eq', $param['uid']];
        $where[] = ['uid', 'eq', $param['from']];
        $curr = $applyMod->where($where)->find();
        if($curr){
            // 加入到对方，和自己的好友列表
            // 自己的
            $data[] = ['g_id' => $param['group'], 'u_id' => $param['uid']];
            // 好友的
            $data[] = ['g_id' => $curr['from_group'], 'u_id' => $param['from']];
            (new GroupUser)->saveAll($data);
            // 写入至用户表
            $friend[] = ['u_id' => $param['uid'], 'f_id' => $param['from']];
            $friend[] = ['u_id' => $param['from'], 'f_id' => $param['uid']];
            (new Friend)->saveAll($data);
            // 写入通知
            $notice = [
                'content' => (new User)->getInfo($param['uid'], 'username').' 已经同意你的好友申请',
                'uid'   => $param['uid'],
                'from'  => '',
            ];
            $applyMod->save($notice);
        }
    }
}
