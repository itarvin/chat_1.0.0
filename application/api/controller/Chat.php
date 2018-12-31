<?php
namespace app\api\controller;
use think\Controller;
use think\facade\Request;
use app\index\model\Chat as chatMod;
use app\index\model\User;

class Chat extends Controller{

    public function initialize(){
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Max-Age: 1728000');
    }
    
    /**
     *文本消息的数据持久化
     */
    public function save_message()
    {
        if($this->request->isAjax()){
            $chatMod = new chatMod;
            $param = $this->request->param();
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
            $param = $this->request->param();
            if(isset($param['id']) && isset($param['type']) && $param['type'] == 'friend' && isset($param['user_id'])){
                // 获取聊天信息
                $data = (new chatMod)->pages($param);
                return ['code' => 200, 'msg' => '', 'data' => $data];
            }else{
                return ['code' => 400, 'msg' => '', 'data' => ''];
            }
        }
    }
}
