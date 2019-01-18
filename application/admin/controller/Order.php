<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\common\model\Order as orderMod;
use app\common\model\OrderLogs;
use app\common\model\Member;
use app\admin\model\Admin;
use think\facade\Cookie;
/**
 * 订单
 */
class Order extends Base{

    /**
     * 订单列表
     * @return view
     */
    public function index()
    {
        $orderMod = new orderMod;
        $data = $orderMod->pages($this->param);
        $this->assign([
            'data' => $data,
            'express' => enjson(variable('express')),
        ]);
        return $this->fetch();
    }

    /**
     * 订单回收站列表
     * @return view
     */
    public function recycle()
    {
        $orderMod = new orderMod;
        $this->param['@onlyTrashed'] = true;
        $data = $orderMod->pages($this->param);
        $this->assign([
            'data' => $data,
            'express' => enjson(variable('express')),
        ]);
        return $this->fetch();
    }

    /****
     * 撤回回收站商品
    */
    public function recall()
    {
        $orderMod = new orderMod;
        if(!getval($this->param, 'id')){
            return $this->buildReturn(apiReturn('',0, '参数错误，请核对后再试！！'),url('order/index'));
        }
        $orderMod = $orderMod->onlyTrashed()->find($this->param['id']);
        $orderMod->restore();
        $this->success('恢复成功！');
    }

    /****
     * 删除订单内商品
    */
    public function delgoods($id)
    {
        $orderMod = new orderMod;
        if(!getval($this->param,'id') || !is_numeric($this->param['id'])){
            return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！'));
        }
        $result = $orderMod->del_goods($this->param);
        return $this->buildReturn($result);

    }

    /**
     * 更新订单
     * @return view
     */
    public function edit()
    {
        $orderMod = new orderMod;
        if(!getval($this->param,'id')|| !is_numeric($this->param['id'])){
            return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！'),url('order/index',['show' => 'index']));
        }
        $id = $this->param['id'];
        $data = $orderMod->getAOrder($id);
        if(!$data && getval($this->param,'issoft')){
            $trashOrder = $orderMod::onlyTrashed()->find($id);
            if(!$trashOrder){
                return $this->buildReturn(apiReturn('',0,'当前订单被删除或不存在~'),url('order/index',['show' => 'index']));
            }
            $data = $trashOrder;
        }
        // 获取当前会员其他订单
        $rewhere = [];
        if(getval($data,'userid')){
            $rewhere[] = ['userid', 'eq', $data['userid']];
        }
        $rewhere[] = ['phone', 'eq', $data['phone']];
        $allData = $orderMod->field("id")->whereOr($rewhere)->select();
        $count = 0;
        foreach ($allData as $key => $value) {
            if($value['id'] !== $data['id']){
                $count += 1;
            }
        }
        $data['recode'] = $count;
        //订单授权签名
        $data['sign'] = md5($data['order_sn'].date('Y~m~d').config('blnPay')['MCH_KEY']);

        // 统计日志查看
        $logCount = Cookie::has('log_dot_'.$id);
        $logwhere[] = ['actions', 'eq', '["member","Order","add_log"]'];
        $start = date('Y-m-d H:i:s',strtotime("-3 days"));
        $logsMod = new OrderLogs;
        $logsCount = $logsMod->where($logwhere)->whereTime('log_time', '>=', $start)->count();
        $isSee = '0';
        if(!$logCount){
            $isSee = $logsCount;
        }elseif ($logsCount != $logCount) {
            $isSee = $logsCount;
        }
        $data['action'] = exchangeStat($data['order_status']);
        // 获取其他销售
        $where[] = ['type','eq',1];
        $where[] = ['id','neq',$data['userid']];
        $otherSell = (new Member)->lists($where, '0', "id asc", "id");
        foreach ($otherSell as $key => $value) {
            $otherSell[$key] = userinfo($value['id'], ['id','realname']);
        }
        $this->assign([
            'data' => $data,
            'express' => enjson(variable('express')),
            'payway' => enjson(variable('pay_way')),
            'isSee' => $isSee,
            'otherSell' => $otherSell
        ]);
        return $this->fetch();
    }

    /**
     * 订单删除
     * @return json
     */
    public function delete($id)
    {
        $orderMod = new orderMod;
        if(!$id || !is_numeric($id)){
            return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！'),url('order/index',['show' => 'index']));
        }
        $result = $orderMod->del($this->param);
        return $this->buildReturn($result);
    }

    // 检测数据
    private function check_param($param = [])
    {
        $where = [];
        // 时间条件
        $between = explode(' ~ ', $param['between']);
        $start = isset($between[0]) ? date('Y-m-d 00:00:00',strtotime($between[0])) : '';
        $end = isset($between[1]) ? date('Y-m-d 23:59:59', strtotime($between[1])) : '';
        if($start && $end){
            $diff = date_diff(date_create($start),date_create($end));
            if($diff->format("%R%a") >= 180){
                return $this->buildReturn(apiReturn('',0,'时间间隔不得超过6个月！'),url('order/index'));
            }else if($diff->format("%R%a") < 0) {
                return $this->buildReturn(apiReturn('',0,'开始时间不能大于结束时间'),url('order/index'));
            }
            $where[] = ['add_time', 'between', [$start, $end]];
            // 查询类型
            $pay_type = getval($param,'pay_type') ? $param['pay_type'] : '';
            $keyword = getval($param,'keyword') ? trim($param['keyword']) : '';
            if($keyword != '' && $pay_type != ''){
                $where[] = [$pay_type, 'eq', $keyword];
            }
            // 返回状态和条件
            return apiReturn($where,1,'请处理');
        }else{
            return apiReturn('',0,'必须存在时间区间，且区间不得超过6个月！');
        }
    }

    /**
     * 文件数据统计
     * @return file|json
     */
    public function download()
    {
        $orderMod = new orderMod;
        $param = $this->param;
        // 检测条件
        $result = $this->check_param($param);
        if($result['code'] == 0){
            return $this->buildReturn($result, url('order/index'));
        }
        // 处理文件名的时间
        $between = explode(' ~ ', $param['between']);
        $start = isset($between[0]) ? date('Y-m-d 00:00:00',strtotime($between[0])) : '';
        $end = isset($between[1]) ? date('Y-m-d 23:59:59', strtotime($between[1])) : '';
        if($start[0] == $end[0]){
            $preFile = substr($start,0,-9).'至'.substr(substr($end,0,-9),-5);
        }else{
            $preFile = substr($start,0,-9).'至'.substr($end,0,-9);
        }
        //支付状态
        // if($param['pay_status']!=''){
        //     $preFile .= pay_stat($param['pay_status']);
        // }
        // 导出数 15500测试为最大导出数临界值，稳定状态为12000条左右！
        $export = '5000';
        if( getval($param,'download') && $param['download'] == 1){
            // 文件名
            $excelFileName = $preFile.'_'.$param['page'];
            // 计算数据
            $data = $orderMod->field("order_sn,trade_id,add_time,order_amount,area,phone,address,consignee,pay_status,order_status")
            ->where($result['data'])
            ->page($param['page'],$export)
            ->order('id','asc')
            ->select()
            ->toArray();
            // 执行下载文件
            \tools\Tools::exportexcel($data,$excelFileName);
        }else {
            $data = [];
            $count = $orderMod->where($result['data'])->count();
            $page = ceil($count/$export);
            // 文件名
            $excelFileName = $preFile;
            // 拼接链接
            $this->param['download'] = 1;
            for ($i = 1; $i <= $page; $i++) {
                $data[] = ['file' => $excelFileName.'_'.$i.'.xlsx','url' => url("order/download").'?page='.$i.'&'.http_build_query($this->param)];
            }
            // 映射至静态文件
            return $this->fetch('',['data' => $data]);
        }
    }

    /**
     * 批量修改状态
     * @return file|json
     */
    public function deal_action()
    {
        $orderMod = new orderMod;
        if(!getval($this->param,'action') || !getval($this->param,'checked')){
            return $this->buildReturn(apiReturn('',1,'参数不合法！'));
        }
        $result = $orderMod->checked_action($this->param);
        return $this->buildReturn($result);
    }

     /**
      * 改价
      * @return file|json
      */
     public function change_price()
     {
         $orderMod = new orderMod;
         if(!getval($this->param, 'price') && (!getval($this->param, 'order_id') || !getval($this->param, 'info'))){
             return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！！'));
         }
         $result = $orderMod->updeta_price($this->param);
         return $this->buildReturn($result);
     }

     /**
      * 加载记录数据
      * @return file|json
      */
     public function load_record($id = '')
     {
         if(!$id){
             return apiReturn('',0,'订单ID错误！');
         }
         $orderMod = new orderMod;
         $order = $orderMod->getAOrder($id);
         if(!$order){
             $trashOrder = $orderMod::onlyTrashed()->find($id);
             if(!$trashOrder){
                 return apiReturn('',0,'订单不存在或已删除！');
             }
             $order = $trashOrder;
         }
         if(getval($order,'userid')){
             $rewhere[] = ['userid', 'eq', $order['userid']];
         }
         $rewhere[] = ['phone', 'eq', $order['phone']];
         $field = 'id,order_sn,order_amount,consignee,add_time,pay_status,order_status,pay_amount,phone,sell_id';
         $data = $orderMod->field($field)
         ->whereOr($rewhere)
         ->where([['id', 'neq', $id]])
         ->append(['sell_name'])
         ->limit(20)->order('id desc')
         ->select();
         $result = [];
         foreach ($data as $key => $value) {
             if($value['id'] != $this->param['id']){
                 $result[] = $value;
             }
         }
         foreach ($result as $k => $v) {
             $result[$k]['orderstatus'] = variable($v['order_status'],'order_status');
             $result[$k]['paystatus'] = variable($v['pay_status'],'pay_status');
         }
         return apiReturn($result,1,'获取成功！');
     }

     /**
      * 加载日志数据
      * @return file|json
      */
     public function load_logs()
     {
         // 获取当前订单所有的日志
         $where = [];
         if(getval($this->param,'id')){
             $where[] = ['order_id','eq',$this->param['id']];
         }
         $logwhere[] = ['actions', 'eq', '["member","Order","add_log"]'];
         $start = date('Y-m-d H:i:s',strtotime("-3 days"));
         $logsMod = new OrderLogs;
         $logsCount = $logsMod->where($logwhere)->whereTime('log_time', '>=', $start)->count();
         Cookie::forever('log_dot_'.$this->param['id'],$logsCount);
         $data = $logsMod->lists($where);
         foreach ($data as $key => $value) {
             if($value['actions'][0] == 'admin'){
                 $user['username'] = (new Admin)->getAdminInfo($value['userid'], "username");
                 $prefix = '（后台）';
             }else{
                 $user = userinfo($value['userid'], ["username","sellid"]);
                 if($user['sellid'] != ''){
                     $prefix = '（销售）';
                 }else{
                    $prefix = '（客户）';
                 }
             }
             $data[$key]['username'] = $prefix.$user['username'];
         }
         return apiReturn($data,1,'获取成功！');
     }

     /**
      * 清除对应的回收站数据
      * @return file|json
      */
     public function clear_recycle()
     {
         $orderMod = new orderMod;
         if(!getval($this->param, 'action') || !in_array($this->param['action'], ['delete', 'clear'])){
             return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！！'));
         }
         $result = $orderMod->del_recycle($this->param);
         return $this->success('成功删除'.$result['success'].'条，失败'.$result['fail'].'条！失败原因：仅能删除60天前订单！');
     }

     /**
      * 加载日志数据
      * @return file|json
      */
     public function change_addr()
     {
         if(!getval($this->param, 'id')){
             return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！！'));
         }
         $orderMod = new orderMod;
         $result = $orderMod->deal_address($this->param);
         return $this->buildReturn($result);
     }

     /**
      * 变更支付方式
      * @return file|json
      */
     public function change_payway()
     {
         if(!getval($this->param, 'id')){
             return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！！'));
         }
         $orderMod = new orderMod;

         $result = $orderMod->deal_payway($this->param);
         return $this->buildReturn($result);
     }

     /**
      * 变更状态
      * @return json
      */
     public function dealstatus()
     {
         $param = $this->param;
         if(!getval($param, 'sid') || !getval($param, 'order_sn')){
             return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！！'),url('index/index'));
         }
         if(!is_numeric($param['sid'])){
             return $this->buildReturn(apiReturn('',0,'参数错误，请核对后再试！！'),url('index/index'));
         }
         $orderMod = new orderMod;
         $current = $orderMod->getAOrder($param['order_sn'],'id');
         $result = $orderMod->order_stat($current, $param['sid'], $param);
         return $this->buildReturn($result);
     }

     /**
      * 变更购买数量
      * @return json
      */
     public function change_num()
     {
         $param = $this->param;
         if(!getval($param, 'order_sn') || !getval($param, 'num') || !getval($param, 'order_detail')){
             return $this->buildReturn(apiReturn('',0,'参数错误！'),url('index/index'));
         }
         $orderMod = new orderMod;
         $result = $orderMod->changeNum($param);
         return $this->buildReturn($result);
     }


     /**
 	* 转接订单！
 	* @return json
 	*/
 	public function order_exchange($target_id='', $order_sn='')
 	{
 		$orderMod = new orderMod;
        $userinfo = $this->adminInfo;
 		$result = $orderMod->exchange_order($target_id, $order_sn, $userinfo);
 		return $result;
 	}
}
