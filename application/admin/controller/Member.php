<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\common\model\Member as memberMod;
use app\common\model\MemberPoint;
use app\common\model\MemberAddress;
use app\common\model\GoodsCart;
/**
 * 会员
 */
class Member extends Base{

    /**
     * 会员列表
     * @return view
     */
    public function index()
    {
        $memberMod = new memberMod;
        $where = [];
        if(!isset($this->param['type']) || !isset($this->param['type'])){
            return $this->buildReturn(apiReturn('', '0','参数错误，请核对后再试！'),url('admin/index/index'));
        }
        $data = $memberMod->pages($this->param);
        $type = $this->param['type'];
        $username = '';
        if(isset($this->param['parent_id'])){
            $username = userinfo($this->param['parent_id'],'username');
        }
        // var_dump($data);die;
        $this->assign([
            'data'      => $data,
            'type'      => $type,
            'username'  => $username
        ]);
        return $this->fetch();
    }

    /**
     * 会员列表
     * @return view
     */
    public function sales()
    {
        $memberMod = new memberMod;
        $where = [];
        if(!isset($this->param['type'])){
            return $this->buildReturn(apiReturn('', '0','参数错误，请核对后再试！'),url('admin/index/index'));
        }
        $data = $memberMod->pages($this->param);
        // 获取销售列表
        $mWhere[] = ['type','neq', 0];
        $member = $memberMod->lists($mWhere, 0, "", "id,username,type");
        $type = $this->param['type'];
        $username = '';
        if(isset($this->param['parent_id'])){
            $username = userinfo($this->param['parent_id'],'username');
        }
        $this->assign([
            'data'      => $data,
            'type'      => $type,
            'username'  => $username
        ]);
        return $this->fetch('index');
    }

    /**
     * 查看,修改会员
     * @return view
     */
    public function edit($id='')
    {
        if(!$id){
            return $this->error('会员ID不得为空！',url('member/index'));
        }
        $id = $this->param['id'];
        $memberMod = new memberMod;
        if($this->request->isPost()){
			$result = $memberMod->store($this->param);
            return $this->buildReturn($result,url('member/edit',['id' => $id]));
        }
        $data = $memberMod->append(['info'])->find($id);
        $data['member'] = userinfo($data['parent_id'], "username");
        //$where[] = ['userid', 'eq', $data['id']];
        //$data['address'] = (new MemberAddress)->lists($where,0);
        $this->assign([
            'data' => $data,
        ]);

        return $this->fetch();
    }

    public function show($id=''){
        if(!$id){
            return $this->error('会员ID不得为空！',url('member/index'));
        }
        $memberMod = new memberMod;
        $data = $memberMod->append(['info','address'])->find($id);
        //$where[] = ['userid', 'eq', $data['id']];
        //$data['address'] = (new MemberAddress)->lists($where,0);
        $this->assign([
            'data' => $data,
        ]);
        return $this->fetch();
    }

    /**
     * 撤回会员
     * @return json
     */
    public function recall()
    {
        if(!getval($this->param, 'id') || !is_numeric($this->param['id'])){
            return $this->buildReturn(apiReturn('', '0','参数错误，请核对后再试！'),url('member/index'));
        }
        $memberMod = memberMod::onlyTrashed()->find($this->param['id']);
        $memberMod->restore();
        $this->success('恢复成功！');
    }

    /**
     * 删除会员
     * @return json
     */
    public function delete()
    {
        $memberMod = new memberMod;
        $result = $memberMod->del($this->param['id']);
        return $this->buildReturn($result);
    }

    /**
     * 删除会员
     * @return json
     */
    public function onduty($id)
    {
        $memberMod = new memberMod;
        if(!$id || !is_numeric($id)){
            return $this->buildReturn(apiReturn('', '0','参数错误，请核对后再试！'));
        }
        $current = $memberMod->getUserInfo($id);
        $newOnduty = $current['onduty'] ? 0 : 1;
        if($memberMod->save(['onduty' => $newOnduty],['id' => $id])){
            $memberMod->dealCache('member', $id);
			$this->success('变更成功！');
		}else {
			$this->error('变更成功！稍后再试！');
		}
    }

    /**
     * 会员积分
     * @return json
     */
    public function point()
    {
        $pointMod = new MemberPoint;
        $data = $pointMod->pages($this->param);
        return $this->fetch('',['data' => $data]);
    }

    /**
     * 购物车列表
     * @return view
     */
    public function goodscart()
    {
        $gcModel = new GoodsCart;
        $list = $gcModel->pages($this->param);
        return $this->fetch('',['list' => $list]);
    }

    /**
     * 删除购物车
     * @return json
     */
    public function delcart()
    {
        $gcModel = new GoodsCart;
        $result = $gcModel->del();
        if($result){
            $this->success('执行操作完成！成功'.$result['success'].'条！失败'.$result['error'].'条！','member/goodscart');
        }
    }
}
