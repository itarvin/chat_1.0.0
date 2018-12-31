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
    protected function initialize(){
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Max-Age: 1728000');
        $this->param = $this->request->param();
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
        $data['id'] = (string)$data['id'];
        $feiend = $groupMod->lists($data['id']);
        $group = (new Crowd)->select();
        $result = ['mine' => $data, 'friend' => $feiend, 'group' => $group];
        return json(['code' => 0, 'msg' => '', 'data' => $result]);
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
        $data = $userMod->getInfo($param['user_id']);
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
            return $userMod->store($param);
        }
    }
}
