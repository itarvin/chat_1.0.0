<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\index\model;
use app\index\validate\userValidate;
use app\index\model\Group;
use think\Model;
class User extends Model{

    /***
     * 获取指定管理员信息
     * $userid      用户索引
     * $field       输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getInfo($userid, $field = "")
    {
        $res = $this->where('id', $userid)->find();
        if($res){
            if(is_array($field)){
                $retn = [];
                foreach ($field as $key => $value) {
                    $retn[$value] = isset($res[$value]) ?$res[$value] : [] ;
                }
                $res = $retn;
            }elseif($field && is_string($field)){
                $res = isset($res[$field]) ? $res[$field] : '';
            }
        }
        return $res;
    }


    /***
     * 获取指定管理员信息
     * $userid      用户索引
     * $field       输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function store($data)
    {
        if(isset($data['user_id'])){
            if($this->allowField(true)->save($data,['id' => $data['user_id']])){
                return ['code' => 1, 'msg' => '更新账户成功', 'data' => ''];
            }else{
                return ['code' => 0, 'msg' => '网络错误！刷新看看~', 'data' => ''];
            }
        }
    }


    public function regis($param)
    {
        $validate = new userValidate;
        if (!$validate->scene('register')->check($param)) {
            return apiReturn('',0,$validate->getError());
        }
        $exists = $this->where('email', $param['email'])->find();
        if($exists){
            return apiReturn('',0,'当前邮箱已经存在！请登录',url('login/index'));
        }
        $param['username'] = random(10,'all');
        // 处理数据
        $verify         = random(10);
        $param['verify'] = $verify;
        $param['id'] = intval($this->onlyId());
        $param['password'] = $this->encrypt_password($param['password'], $verify);
        $param['status'] = 0;
        $user = self::create($param, ['username', 'email','password','verify','status','id']);
        if($user){
            // 发送邮件检验
            $title = '注册账户通知';
            $url = request()->domain().url('index/login/verification', ['sign' => $user->id, 'info' => md5($user->username)]);
            sendMail($param,$url,$title);
            cache("regedit:".$user->id, time(), 3600);
            // 生成默认分组
            $data = [
                'groupname' => '我的好友',
                'user_id'   => $user->id
            ];
            $group = Group::create($data);
            $gourpData = [
                'g_id'  => $group->id,
                'u_id'  => $user->id
            ];
            (new GroupUser)->save($gourpData);
            return apiReturn('',1,'注册邮件已发送，请在1小时内完成验证~',url('login/index'));
        }else{
            return apiReturn('',0,'网络错误！请稍后再试~');
        }
    }


    /**
     * 所有用到密码的不可逆加密方式
     * @author rainfer <chnitarvin@gmail.com>
     * @param string $password
     * @param string $password_salt
     * @return string
     */
    private function encrypt_password($password, $password_salt)
    {
        return md5($password . md5($password_salt));
    }

    /**
     * 所有用到密码的不可逆加密方式
     * @author rainfer <chnitarvin@gmail.com>
     * @param string $password
     * @param string $password_salt
     * @return string
     */
     private function onlyId()
     {
         $onluID = random(5,'number');
         $isExist = (new User)->field('id')->where('id',$onluID)->find();
         if($isExist){
             return $this->onlyId();
         }
         return $onluID;
     }


     public function login($param)
     {
         $validate = new userValidate;
         if (!$validate->scene('login')->check($param)) {
             return apiReturn('',0,$validate->getError());
         }
         $user = $this->where('username|email', $param['username'])->find();
         if(!$user || ($this->encrypt_password($param['passwords'], $user['verify']) != $user['password'])){
             return apiReturn('',0,'账户密码错误~');
         }
         session('layimId', $user['id']);
         return apiReturn('',1,'登录成功，正在安全检测并登录~',url('index/index'));
     }


     /**
     * 应用场景：读取列表
     * @param string $val    当前数据
     * @return string
     */
    public function lists($where, $len = '15', $sort = 'id desc', $field = '*')
	{
        $res = $this->field($field)->where($where)->order($sort)->limit($len)->select();
        return $res;
    }
}
