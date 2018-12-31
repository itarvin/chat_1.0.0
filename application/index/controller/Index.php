<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use app\Util\ReturnCode;
use app\Util\Tools;
class Index extends Controller
{
    protected $param;
    protected function initialize(){
        $this->param = $this->request->param();
    }

    public function index()
    {
        // $form = '100000';
        $param  = $this->param;
        if(!isset($param['id'])){
            $form = '100000';
        }else{
            $form = $param['id'];
        }
        $this->assign([
            'form' => $form
        ]);
        if($this->request->isMobile()){
            return $this->fetch('mbindex');
        }else{
            return $this->fetch();
        }
    }
}
