<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\common\model;
use app\common\validate\Member as memberValidate;
use app\common\validate\Article as articleValidate;
use app\member\service\VerificatCode;
use app\common\service\PubliclyWhere;
use think\model\concern\SoftDelete;
use app\common\model\MemberInfo;
use app\common\model\MemberPoint;
use app\admin\service\AdminCheck;
use think\facade\Cookie;
/**
 * 会员用户模型
 * @package app\admin\model
 */
class Member extends Common{

    use SoftDelete;
    protected $defaultSoftDelete = null;
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime         = 'add_time';
    protected $updateTime         = false;

    /**
     * 应用场景：自动补齐头像
     * @param string $val    当前数据
     * @param string $data    所有数据
     * @return string
     */
    protected function getPhotoAttr($val){
        if($val){
            $val = bucketFix($val);
        }else{
            $val='/static/images/avatar.jpg';
        }
        return $val;
    }

    /**
     * 应用场景：去除头像域名
     * @param string $val    当前数据
     * @param string $data    所有数据
     * @return string
     */
    protected function setPhotoAttr($val, $data){
        if($val=='/static/images/avatar.jpg'){
            $val='';
        }
        if($val){
            $val = bucketFix($val, false);
        }
        return $val;
    }

    /**
     * 应用场景：追加二维码域名
     * @param string $val    当前数据
     * @param string $data    所有数据
     * @return string
     */
    protected function getQrcodeAttr($val){
        return bucketFix($val);
    }

    /**
     * 应用场景：获取所属销售ID
     * @param string $val    当前数据
     * @param string $data    所有数据
     * @return int
     */
    protected function getParentIdAttr($val,$data){
        //如当前为销售，parent_id为自己
        if($data['type'] > 0){
            $val = $data['id'];
        }
        return $val;
    }

    //虚拟字段：会员详细信息
    public function getInfoAttr($val,$data){
        $res = (new MemberInfo)->where('userid',$data['id'])->find();
        return $res;
    }

    //虚拟字段：会员地址
    public function getAddressAttr($val, $data){
        $res=MemberAddress::where('userid',$data['id'])->select();
        return $res;
    }

    /**
     * 应用场景：新增，修改数据时的数据验证与处理
     * @param string $data    所有数据
     * @return array
     */
    public function store($data)
    {
		if(isset($data['id'])){
            // 处理数据
            $validate = new memberValidate;
            if (!$validate->scene('edit')->check($data)) {
                // 验证数据失败
                return apiReturn('',0,$validate->getError());
            }
            if(getval($data, 'sellid')){
                $sellid = $this->field('id,type')->where('sellid', $data['sellid'])->find();
                if($sellid && $sellid['type'] == '0'){
                    unset($data['sellid']);
                }
                if($sellid && $sellid['id'] != $data['id']){
                    return apiReturn('',0,'sellid已经存在！请更换！');
                }
                cache('pan_fix',null);//清除泛解析前缀缓存
                $this->dealCache('userinfo', [$data['id']]);
            }
            if(isset($data['photo']) && isset($data['qrcode'])){
                checkImg([$data['photo'], $data['qrcode']]);
            }
            if(isset($data['born']) && !strtotime($data['born'])){
                unset($data['born']);
            }

			if($this->allowField(true)->save($data,['id' => $data['id']])){
                // 清除缓存
                $this->dealCache('userinfo', $data['id']);
                // 区别第一次还是更新信息
                $miModel = new MemberInfo;
                $all = $miModel->where('userid',$data['id'])->find();
                if($all){
                    $miModel->allowField(true)->save($data,['userid' => $data['id']]);
                }else {
                    $data['userid'] = $data['id'];
                    unset($data['id']);
                    $miModel->allowField(true)->save($data);
                }
                return apiReturn('',1,'提交处理成功！');
			}
		}
        return apiReturn('',0,'网络错误，请稍后再试！');
    }

    /**
     * 修改用户密码
     * @param $array
     * @return json
     */
    public function editpass($data)
    {
        // 处理数据
        $validate = new memberValidate;
        if (!$validate->scene('editpass')->check($data)) {
            // 验证数据失败
            return apiReturn('',0,$validate->getError());
        }
        $is_admin = getval($data,'is_admin') ? true : false;
        $unique = $this->field('id')->where('username', $data['username'])->find();
        if(!$is_admin){
            if($unique && $unique['id'] != $data['id']){
                return apiReturn('',0,'用户名已经存在！');
            }
        }
        $member = $this->find($unique['id']);
        $is_admin = getval($data,'is_admin') ? true : false;
        if($member && !$is_admin){
            if(getval($data,'oldpass') && ($member['password'] != '')){
                if($data['oldpass'] == ''){
                    return apiReturn('',0,'原密码不能为空！请校正');
                }elseif(self::encrypt_password($data['oldpass'], $member['encrypt']) != $member['password']){
                    return apiReturn('',0,'原密码错误！请校正');
                }
            }
        }
        $verify           = random(10);
        $data['encrypt']  = $verify;
        $data['password'] = self::encrypt_password($data['newpass'], $verify);
        if($this->allowField(true)->save($data,['id' => $member['id']])){
            // 清缓存
            $this->dealCache('userinfo', $member['id']);
            // 清除cookie
            cookie('member_token', null);
            return apiReturn('',1,'更新账户密码成功！');
        }else {
            return apiReturn('',0,'网络错误，请稍后再试！');
        }
    }

    /**
     * 获取所有信息
     * @param $param 查询条件
     * @param $field 输出字段
     * @param $sort  排序
     * @param $len   输出条数
     * @return $data 数据集
     */
    public function pages($param = [], $len = '15', $sort = "id desc", $field = "*")
    {
        $where = PubliclyWhere::where($param, 'add_time','title');
        $allowCheck=['id'=>'eq','phone'=>'eq','username'=>'eq','nickname'=>'like','qq' => 'eq', 'weixin' => 'eq'];
        $keyword = getval($param,'keyword');
        $ktype = getval($param,'ktype');
        if($chk = getval($allowCheck,$ktype) && $keyword){
            if($chk=='like'){
                $where[] = [$param['ktype'], 'like', '%'.$keyword.'%'];
            }else{
                $where[] = [$param['ktype'], $chk, $keyword];
            }
        }
        if(isset($param['parent_id'])){
            $where[] = ['parent_id', 'eq', $param['parent_id']];
        }
        if(getval($param, 'type') && $param['type'] == 'softdelete'){
            $where['@onlyTrashed'] = 'true';
        }elseif (isset($param['type']) && is_numeric($param['type'])) {
            $where[] = ['type' , 'eq', $param['type']];
        }
        $data = $this->build($where, '', $sort, $field)->append(['info'])->paginate($len, false, ['query' => $param]);
        return $data;
    }

    /**
     * 获取所有信息
     * @param $where 查询条件
     * @param $field 输出字段
     * @param $sort  排序
     * @param $len   输出条数
     * @param $page  是否分页
     * @return $data 数据集
     */
    public function lists($where = [], $len = '15', $sort = "id desc", $field = "*")
    {
        $data = $this->build($where, $len, $sort, $field)->append(['info'])->select();
        return $data;
    }

    /**
     * 删除会员
     * @param int $id
     * @return json
     */
    public function del($id)
    {
        if($this->destroy($id)){
            $this->dealCache('userinfo', $id);
            return apiReturn('',1,'删除成功！');
        }else{
            return apiReturn('',0,'网络错误，请稍后再试！');
        }
    }
    //获取所有sellid数组
    public function get_pan(){
        $res = self::where([['sellid','<>',''],['type','>',0]])->field('sellid')->cache('pan_fix')->select();
        $pan = [];
        foreach ($res as $key => $vo){
            $pan[]=$vo['sellid'];
            # code...
        }
        return $pan;
    }

    // ------------------会员登录---------------
    /**
     * 用户登录
     * @param string $username   用户名
     * @param string $password   密码
     * @param bool   $rememberme 记住登录
     * @param bool   $phone     手机号码
     * @return bool|mixed
     * @throws
     */
    public function login($username, $password = '', $codemsg = '', $remember = false)
    {
    	if(!$username){
            return apiReturn('',0,'你怎么通过验证的呢？？');
        }
        $userInfo = [];
        if($username && $password){
            $userInfo  = $this->where([['username|phone','eq',$username]])->find();
            if (!$userInfo) {
                return apiReturn('',0,'账户或密码错误！');
            }elseif ($userInfo['password'] == '') {
                return apiReturn('',0,'当前账户暂仅支持短信登录！');
            } else {
                if (self::encrypt_password($password, $userInfo['encrypt']) !== $userInfo['password']) {
                    return apiReturn('',0,'账户或密码错误！');
                }
            }
            // 账户是否未审核或已锁定
            if($userInfo['status'] == 2) {
                return apiReturn('',0,'您的账户被锁定！请联系管理员处理！');
            }
            $data['login_time'] = date('Y-m-d H:i:s',time());
            $result = $this->save($data,['id' => $userInfo['id']]);
            if (!$result) {
                return apiReturn('',0,'网络错误，请稍后再试！');
            }
            self::autoLogin($userInfo, $remember);
            $this->dealCache('userinfo', $userInfo['id']);
        }elseif($username && $codemsg){
            // 校验验证码
            $result = VerificatCode::checkVerifyCode($username,$codemsg);
            if($result['code'] != 1){
                return apiReturn($result['data'],0,$result['msg']);
            }
            $userInfo  = $this->where([['phone','eq',$username]])->find();
            // 账户是否未审核或已锁定
            if($userInfo['status'] == 2) {
                return apiReturn('',0,'您的账户被锁定！请联系管理员处理！');
            }
            if(!$userInfo){
                $verify = random(10);
                $parent_id = saleinfo('id') ? saleinfo('id') : 0;
                $data = [
                    'phone'    => $username,
                    'encrypt'  => $verify,
                    'status'   => 1,
                    'login_ip' => request()->ip(),
                    'login_time' => date('Y-m-d H:i:s',time()),
                    'parent_id' => $parent_id, //如存在销售ID则写入，否则为0
                ];
                // 创建账户
                $member = self::create($data);
                if(!$member){
                    return apiReturn('',0,'登录失败!稍后再试！');
                }
                // 新用户默认写入100积分
                (new MemberPoint)->add(100,$member['id'],'register',1);
                self::autoLogin($member, 'false');
            }else{
                // 账户是否未审核或已锁定
                if($userInfo['status'] == 2) {
                    return apiReturn('',0,'您的账户被锁定！请联系管理员处理！');
                }
                $data['login_time'] = date('Y-m-d H:i:s',time());
                $result = $this->save($data,['id' => $userInfo['id']]);
                if (!$result) {
                    return apiReturn('',0,'网络错误，请稍后再试！');
                }
                self::autoLogin($userInfo, 'false');
            }
        }
        $pan = getPan();
        //未标记的普通用户，登录某销售网址，自动标记
        if($userInfo['type']==0 && $userInfo['parent_id']==0 && $pan){
            $parent_id=userinfo($pan,'id');
            if($parent_id){
                $userInfo->parent_id=$parent_id;
                $userInfo->save();
            }
        }

        $sell_doman = '';
        if($userInfo['type'] && $userInfo['sellid'] && $pan){
    		$sell_doman = $userInfo['sellid'].'.'.request()->rootDomain();
    	}elseif($userInfo['parent_id']){
            $pan = userinfo($userInfo['parent_id'],'sellid');
            $pan = $pan?$pan:'www';
            $sell_doman = $pan.'.'.request()->rootDomain();
    	}
        if(request()->domain() == $sell_doman){
            $jumpurl = url('member/index/index', [], '', $sell_doman);
            return apiReturn('',1,'登录成功！',$jumpurl);
        }else{
            $sign = md5($userInfo['phone'].$userInfo['encrypt'].date('Y-m-d',time()));
            $jumpurl = url('member/login/index', ['userid' => $userInfo['id'],'sign' => $sign], '', $sell_doman);
            return apiReturn('redirect',1,'登录成功~，正在为您做安全跳转！',$jumpurl);
        }
    }

    /**
     * 自动登录
     * @param mixed $member       用户对象
     * @param bool  $rememberme 是否记住登录，默认7天
     * @throws
     */
    public static function autoLogin($member, $rememberme = true)
    {
        session('member_auth', $member['id']);
        // 记住登录
        if ($rememberme) {
            $salt = $member['id']."-".self::pwd_encrypt($member['id'],$member['encrypt']);
            cookie('member_token', $salt, 24 * 3600 * 7);
        }
    }

    /**
     * 判断是否登录
     * @return int 0或用户id
     * @throws
     */
    public function isLogin()
    {
        $user = session('member_auth');
        if (!$user) {
            $selfToken = cookie('member_token');
            if($selfToken){
                $sign = AdminCheck::breakSign($selfToken);
                if($sign){
                    $userInfo = self::field('id,password,verify')->find($sign['id']);
                    if(md5($userInfo['id'].$userInfo['password'].$userInfo['verify']) == $sign['sign']){
                        session('member_auth', $sign['id']);
                        cookie('member_token', $selfToken, 24 * 3600 * 7);
                        return true;
                    }
                }
            }
            return false;
        }
        return true;
    }

    //密码加密方式--勿修改--
    private static function pwd_encrypt($password,$salt)
    {
        return md5(sha1($password).sha1($salt));
    }

    /**
     * 所有用到密码的不可逆加密方式
     * @author rainfer <81818832@qq.com>
     * @param string $password
     * @param string $password_salt
     * @return string
     */
    private static function encrypt_password($password, $password_salt)
    {
        return md5($password . md5($password_salt));
    }

    /***
     * 获取指定会员信息-新
     * $userid      用户索引，可以是数组条件，或字符串（检索：id/sellid/phone）
     * $field       输出字段，为空时输出所有，可以为字符串，或数组
    */
    public function getUserInfo($userid, $field = "")
    {
        //映射数组
        $userMap = cache('userMap');
        $userMap = $userMap ? $userMap :[];
        $user = '';
        if(is_numeric($userid) || is_string($userid)){
            $userid = getval($userMap, $userid, $userid);
            $user = cache('userinfo_'.$userid);
        }

        if(!$user){
            if(is_array($userid)){
                $where = $userid;
            }elseif(is_numeric($userid)){
                $where = [['a.id|a.phone', '=', $userid]];
            }else{
                $where = [['a.sellid', '=', $userid]];
            }
            $user = $this->alias('a')->leftJoin('member_info b','a.id = b.userid')
            ->hidden(['encrypt','password'])->where($where)->find();
            if($user){
                $userid = $user['id'];
                $userMap['phone'] = $userid;
                $userMap['sellid'] = $userid;
                unset($user['password'],$user['encrypt']);
                cache('userinfo_'.$user['id'], $user);
            }
        }
        if($field && is_string($field)){
            return getval($user,$field,'');
        }elseif(is_array($field)){
            if($user){
                $retn=[];
                foreach ($field as $key => $vo) {
                    $retn[$vo] = getval($user,$vo,'');
                }
            }else{
                $retn = '';
            }
            return $retn;
        }else{
            return $user;
        }
    }
}
