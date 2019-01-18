<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
class Base extends Controller
{
    protected $param;
    protected $isLogin;
    protected function initialize(){
        $this->param = $this->request->param();
        $this->isLogin = session("layimId");
        if(!$this->isLogin){
            $this->error("您需要登录账户哦！",url('login/index'));
        }
    }
}
