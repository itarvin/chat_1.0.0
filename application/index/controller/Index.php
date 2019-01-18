<?php
namespace app\index\controller;
use app\index\model\User;
class Index extends Base
{

    /*
    * 应用场景: 主页
    * @param
    */
    public function index()
    {
        $this->assign([
            'form' => $this->isLogin
        ]);
        if($this->request->isMobile()){
            return $this->fetch('mbindex');
        }else{
            return $this->fetch();
        }
    }

    /*
    * 应用场景: 主页
    * @param
    */
    public function find()
    {
        $lists = [];
        $userMod = new User;
        $field = 'id,username,avatar,sign,online,email';
        $wh[] = ['id', 'neq', $this->isLogin];
        $nominate = $userMod->lists($wh, '12', '', $field);
        if($this->request->isPost()){
            $where[] = ['username|id', 'eq', $this->param['number']];
            $lists = $userMod->lists($where, '0', '', $field);
        }
        $this->assign([
            'lists' => $lists,
            'nominate'  => $nominate
        ]);
        return $this->fetch();
    }

    /*
    * 应用场景: 主页
    * @param
    */
    public function msgbox()
    {
        return $this->fetch();
    }

    /*
    * 应用场景: 主页
    * @param
    */
    public function myinfo()
    {
        return $this->fetch();
    }

    /*
    * 应用场景: 主页
    * @param
    */
    public function chatlog()
    {
        return $this->fetch();
    }
}
