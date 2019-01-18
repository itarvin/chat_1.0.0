<?php
namespace app\admin\controller;
use app\common\model\Admin as adminModel;
use app\Util\ReturnCode;
use app\Util\Tools;
/**
 * 应用场景：管理员
 * @since   2018/06/19 创建
 * @author  itarvin itarvin@163.com
 */
class Admin extends Base
{
    /**
     * 应用场景：列表页
     * @return view
     */
    public function index()
    {
        $list = (new adminModel)->getList($this->request->param());
        return $this->fetch('',['list'=> $list]);
    }

    /**
     * 应用场景：新增用户
     * @return json|view
     */
    public function add()
    {
        if($this->request->isPost()){
            return (new adminModel)->store($this->request->param());
        }
        return $this->fetch('');
    }

    /**
     * 应用场景：更新用户
     * @return json|view
     */
    public function edit()
    {
        $model = new adminModel;
        if($this->request->isPost()){
            return $model->store($this->request->param());
        }
        $data = $model->find($this->request->param()['id']);
        return $this->fetch('',['data'=> $data]);
    }


    /**
     * 应用场景：更新用户
     * @return json|view
     */
    public function my()
    {
        $model = new adminModel;
        if($this->request->isPost()){
            return $model->store($this->request->param());
        }
        $data = $model->find(session('userid'));
        return $this->fetch('',['data'=> $data]);
    }

    /**
     * 应用场景：删除用户
     * @return json
     */
    public function delete()
    {
        $model = new adminModel;
        if($this->request->isPost()){
            return $model->del($this->request->param());
        }
    }

    /**
     * 应用场景：更新密码通过邮箱并检测发送！
     * @return jump
     */
    public function entereamil()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $info = (new adminModel)->find(session('userid'));
            if($info['email'] != $data['email']){
                return ['code' => ReturnCode::PARAMEERROR, 'info' => Tools::errorCode(ReturnCode::PARAMEERROR)];
            }else {
                $person = md5($info['username']);
                $url = $this->request->domain().url('admin/door/resetpass', ['sign'=>session('userid'), 'info' => $person]);
                $title = "修改账户密码通知";
                sendMail($info,$url,$title);
                cache("email:".$info['id'], time(), 3600);
                return ['code' => ReturnCode::SUCCESS, 'info' => Tools::errorCode(ReturnCode::SUCCESS)];
            }
        }
    }

    /**
     * 应用场景：QQ绑定
     * @return jump
     */
    public function qqbind()
    {
        $model = new adminModel;
        $data['openid'] = session("openid");
        isQqExist(session("openid"));
        $user = $model->find(session('userid'));
        if(session("openid") != $user['openid'] && $user['openid'] != ''){
            $title = "变更第三方服务通知";
            sendMail($user,'',$title,true,'QQ');
        }elseif ($user['openid'] == '') {
            $title = "关联第三方服务通知";
            sendMail($user,'',$title,true,'QQ');
        }
        if($model->save($data,['id' => session('userid')])){
            $this->success("绑定QQ成功!",url('admin/my'));
        }else{
            $this->error('绑定QQ失败了!',url('admin/my'));
        }
    }


    /**
     * 应用场景：微博绑定
     * @return jump
     */
    public function wbbind()
    {
        $model = new adminModel;
        $data['sinaid'] = $_SESSION["token"]["uid"];
        isWbExist($_SESSION["token"]["uid"]);
        $user = $model->find(session('userid'));
        if($_SESSION["token"]["uid"] != $user['sinaid'] && $user['sinaid'] != ''){
            $title = "变更第三方服务通知";
            sendMail($user,'',$title,true,'微博');
        }elseif ($user['sinaid'] == '') {
            $title = "关联第三方服务通知";
            sendMail($user,'',$title,true,'微博');
        }
        if($model->save($data,['id' => session('userid')])){
            $this->success("绑定微博成功!",url('admin/my'));
        }else{
            $this->error('绑定微博失败了!',url('admin/my'));
        }
    }
}
