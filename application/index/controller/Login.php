<?php
namespace app\index\controller;
use think\captcha\Captcha;
use app\index\model\User;
use think\Controller;
use think\Request;
class Login extends Controller
{

    protected $param;
    protected function initialize(){
        $this->param = $this->request->param();
    }

    public function index()
    {
        if($this->request->isPost()){
            $captcha = new Captcha();
            if(!$captcha->check($this->param['verify'])){
                return json(apiReturn('', 0, '验证码错误！请重新输入~'));
            }
            $res = (new User)->login($this->param);
            return json($res);
        }
        return $this->fetch();
    }

    /**
    * 注册账号
    * @param string $id
    * @return \think\Response
    */
    public function register()
    {
        if($this->request->isPost()){
            $res = (new User)->regis($this->param);
            return json($res);
        }
        return $this->fetch();
    }

    /**
    * 生成验证码
    * @param string $id
    * @return \think\Response
    */
    public function verify()
    {
        $id = getval($this->param,'id') ? $this->param['id'] : '';
        ob_end_clean();
        $config = [
           // 验证码字体大小
           'fontSize'    =>   20,
           // 验证码位数
           'length'      =>   4,
           // 关闭验证码杂点
           'useNoise'    =>   false,
           // 是否画混淆曲线
           'useCurve'    =>   false,
           //背景色
           'bg'          =>   [243, 251, 254],
           // 验证成功后是否重置
           'reset'       =>   true,
        ];
        $captcha = new Captcha($config);
        // 设置验证码字符为纯数字
        $captcha->codeSet = '0123456789';
        return $captcha->entry($id);
    }

    public function verification()
    {
        $param = $this->request->param();
        $sign = cache('regedit:'.$param['sign']);
        if(!$sign){
            $this->error('当前链接已失效！联系管理员重新发送邮件链接1！',url('login/register'));
        }
        // 校验info
        $userMod = new User;
        $username = $userMod->getInfo($param['sign'],"username");
        if(md5($username) != $param['info']){
            $this->error('当前链接已失效！联系管理员重新发送邮件链接2！',url('login/register'));
        }
        cache('regedit:'.$param['sign'], null);
        if($userMod->save(['status' => 1],['id' => $param['sign']])){
            session('layimId', $param['sign']);
            $this->success('激活账户完成,正在跳转至管理...',url('index/index'));
        }else {
            $this->error('激活失败了,联系管理员重新发送邮件链接！',url('login/register'));
        }
    }
}
