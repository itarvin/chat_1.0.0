<?php
namespace app\api\controller;
use app\index\model\User;
use app\index\model\Group;
use app\index\model\Friend;
use app\index\model\Crowd;
use think\Controller;
use think\Request;
class Index extends Controller
{
    protected $param;
    protected $isLogin;
    protected function initialize(){
        $this->param = $this->request->param();
        $this->isLogin = session("layimId");
    }

    public function mine()
    {
        $userMod = new User;
        $groupMod = new Group;
        $param = $this->param;
        if(!isset($param['user_id'])){
            return json(['code' => 400, 'msg' => '', 'data' => '']);
        }
        $data = $userMod->getInfo($param['user_id']);
        $data['status'] = $data['online'];
        $feiend = $groupMod->lists($data['id']);
        $group = (new Crowd)->select();
        $result = ['mine' => $data, 'friend' => $feiend, 'group' => $group];
        return json(['code' => 0, 'msg' => '', 'data' => $result]);
    }

    public function my_info()
    {
        $userMod = new User;
        $field = ['username','avatar','sign','email','born','desc','sex','desc'];
        $userinfo = $userMod->getInfo($this->isLogin,$field);
        return json(['code' => 1, 'msg' => '', 'data' => $userinfo]);
    }

    public function friends()
    {
        $param = $this->param;
        $friendMod = (new Friend);
        $data = $userMod->getInfo($param['user_id']);
        $feiend = $friendMod->lists($data['id']);
        $result = ['owner' => $data, 'member' => count($feiend), 'list' => $feiend];
        return json(['code' => 0, 'msg' => '', 'data' => $result]);
    }

    public function userinfo()
    {
        $userMod = new User;
        $param = $this->param;
        $field = ['id','username','avatar','sign','email','born','desc','sex','desc'];
        $data = $userMod->getInfo($param['user_id'],$field);
        return json(['code' => 0, 'msg' => '', 'data' => $data]);
    }

    /**
     *文本消息的数据持久化
     */
    public function change()
    {
        if($this->request->isAjax()){
            $userMod = new User;
            $param = $this->request->param();
            $param['user_id'] = $this->isLogin;
            return $userMod->store($param);
        }
    }
}
