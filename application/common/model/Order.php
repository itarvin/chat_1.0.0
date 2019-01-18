<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\common\model;
use think\model\concern\SoftDelete;
use app\common\service\PubliclyWhere;
use app\common\validate\Order as orderValidate;
use app\common\model\OrderDetail;
use app\common\model\Member;
use app\admin\model\Admin;
use app\common\model\OrderLogs;
use app\common\model\MemberPonit;
use think\Db;

/**
 * 订单模型
 * @package app\admin\model
 */
class Order extends Common{

    use SoftDelete;
    protected $defaultSoftDelete = null;
    protected $deleteTime = 'del_time';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime         = 'add_time';
    protected $updateTime         = false;
    protected $auto = ['sys_ip','sys_agent'];
    protected $json = ['area','point','coupon'];

    /**
     * 应用场景：提交sellid（转为数字型）
     * @param string $val    当前数据
     * @return int
     */
    protected function setSellIdAttr($val){
        if(!$val){
            $val = saleinfo('id');
            $val = $val ? $val : 0;
        }
        return $val;
    }

    /**
     * 应用场景：设置地址
     * @param string $val    当前数据
     * @return string
     */
    protected function setAreaAttr($vo){
        return preg_split('/\s+/',$vo);
    }

    /**
     * 应用场景：下单ip
     * @param string $val    当前数据
     * @return string
     */
    protected function setSysIpAttr(){
        return request()->ip();
    }

    /**
     * 应用场景：下单agent
     * @param string $val    当前数据
     * @return string
     */
    protected function setSysAgentAttr($val){
        $val=substr(request()->header('User-Agent'),0,220);
        return $val;
    }
    //
    protected function getShortUrlAttr($val, $data){
        if(!$val){
            $val = url("mall/order/detail",["order_sn"=>$data.order_sn,'sign'=>$data.sign],'',true);
        }
        return is_array($val) ? join(',',$val) : $val;
    }
    /**
     * 应用场景：格式化区域
     * @param string $val    当前数据
     * @return string
     */
    protected function getAreaAttr($val){
        return is_array($val) ? join(',',$val) : $val;
    }

    /**
     * 应用场景：订单细节
     * @param string $val    当前数据
     * @return string
     */
    public function getDetailAttr($val, $data){
        $res = (new OrderDetail)->getDetail($data['id']);//where('order_id','=',$data['id'])->select();
        return $res;
    }

    public function logs(){
        return $this->hasMany('order_logs','order_id');
    }

    /**
     * 应用场景：下单agent
     * @param string $val    当前数据
     * @return string
     */
    protected function getSellNameAttr($val, $data){
        if($data['sell_id']){
            $val = userinfo($data['sell_id'],'realname');
        }
        return $val;
    }

    /**
     * 获取所有订单信息
     * @param $where 查询条件
     * @param $field 输出字段
     * @param $sort  排序
     * @param $len   输出条数
     * @return $data 数据集
     */
    public function lists($where = [], $len = 15, $sort='', $field=''){
        $res = $this->build($where, $len, $sort, $field)->append(['detail'])->select();
        return $res;
    }

    /**
     * 获取所有信息分页
     * @param $param 返回参数
     * @param $field 输出字段
     * @param $sort  排序
     * @param $len   输出条数
     * @return $data 数据集
     */
    public function pages($param = [], $len = 15, $sort='id desc', $field = "*")
    {
        $where = [];
        if(getval($param,'searchtype') && getval($param, 'keyword')){
            $where[] = [$param['searchtype'], 'eq', $param['keyword']];
        }
        if(getval($param,'order_status')){
            $where[] = ['order_status', 'eq', $param['order_status']];
        }
        if(getval($param,'pay_status')){
            $where[] = ['pay_status', 'eq', $param['pay_status']];
        }
        if(getval($param, 'type')){
            $type = $param['type'];
            switch ($type) {
                case 'unpay':
                    $where[] = ['pay_status', 'eq', '0'];
                    break;
                case 'unsign':
                    $where[] = ['order_status', 'eq', '5'];
                    break;
                case 'unply':
                    $where[] = ['order_status', 'eq', '8'];
                    break;
                case 'done':
                    $where[] = ['order_status', 'eq', '10'];
                    break;
                }
        }
        if(getval($param, 'sell_id')){
            $where[] = ['sell_id', 'eq', $param['sell_id']];
        }
        if(getval($param, 'userid')){
            $where[] = ['userid', 'eq', $param['userid']];
        }
        if($between = betweendate($param,'add_time')){
            $where[] = $between;
        }
        if(getval($param, 'show')){
            $where['show'] = $param['show'];
        }
        if(getval($param, '@onlyTrashed')){
            $where['@onlyTrashed'] = $param['@onlyTrashed'];
        }
        $data = $this->build($where, '', $sort, $field)->paginate($len, false, ['query' => $param]);
        return $data ? $data : [];
    }


    /**
     * 获取单个订单
     * @param $id 返回参数（可以是订单ID或订单号order_sn）
     * @return $data 数据集
     */
    public function getAOrder($id='', $field = ''){
        $orderid_map = cache('orderid_map');
        $orderid_map = is_array($orderid_map)?$orderid_map:[];
        $id = getval($orderid_map, $id, $id);
        $data = cache('order_'.$id);
        if(!$data && $id){
            $data = $this->where('id|order_sn',$id)->append(['detail'])->find();
            if($data){
                //订单号-ID 映射表缓存
                $orderid_map[$data['order_sn']]=$data['id'];
                cache('orderid_map', $orderid_map);
            }
            //订单缓存
            cache('order_'.$id, $data);
        }
        if($field){
            $data = getval($data,$field);
        }
        return $data;
    }

    /**
     * 清除对应的回收站数据
     * @return file|json
     */
     public function del_recycle($param)
     {
         $success = 0;
         $fail = 0;
         if($param['action'] == 'delete'){
             if(!getval($param, 'checked')){
                 return $this->buildReturn(apiReturn('',0,'参数错误，请核对信息后再试！'));
             }
             $data = $param['checked'];
         }elseif ($param['action'] == 'clear') {
             $result = $this->lists([], "","", '0',true);
             $data = [];
             foreach ($result as $key => $value) {
                 $data[] = $value['id'];
             }
         }
         // 遍历删除
         foreach ($data as $key => $id) {
             $this->dealCache('order', [$id]);
             $tempRe = $this->dealClear($id);
             if($tempRe['code'] == 1){
                 $success += 1;
                 $this->dealCache('order', [$id]);
             }else{
                 $fail += 1;
             }
         }
         return $data = [
             'success' => $success,
             'fail' => $fail,
         ];
     }

     /**
      * 单个判断删除
      * @return file|json
      */
     private function dealClear($id)
     {
         $this->dealCache('order', [$id]);
         $orders = self::onlyTrashed()->find($id);
         $orders->restore();
         $order = $this->field('id,order_sn,add_time')->find($id);
         $diff = dealDate($order['add_time'], date('Y-m-d H:i:s',time()));
         if($diff > 0 && $diff > 60){
             // 启动事务
            Db::startTrans();
            try {
                $orderDrtail = OrderDetail::where('order_id',$order['id'])->delete();
                $orderLogs = OrderLogs::where('order_id',$order['id'])->delete();
                $where[] = ['type','eq','order'];
                $where[] = ['source','eq',$order['id']];
                $where[] = ['status','neq','1'];
                $memberPonit = MemberPoint::where($where)->delete();
                $currentOrder = $this->where('id',$order['id'])->delete();
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
            return apiReturn($order['order_sn'],1,'删除订单【'.$order['order_sn'].'】成功！');
         }else{
             $del = $this->destroy($id);
             return apiReturn($order['order_sn'],0,'删除订单【'.$order['order_sn'].'】失败！原因：仅能删除60天前订单！');
         }
     }

     /**
      * 发货
      * @return file|json
      */
     public function send_goods($param)
     {
         if(!getval($param,'order_sn')){
            return apiReturn('',0,'参数错误，请核对信息后再试！');
         }
         $curOrder = $this->where('order_sn', $param['order_sn'])->find();
         $data = $param;
         $data['order_status'] = '3';
         if(!$curOrder){
             return apiReturn('',0,'当前订单不存在！请核对订单号！');
         }
         if($this->save($data, ['id' => $curOrder['id']])){
             $note = '确认发货中，快递公司为"'.variable($param['exp_com'], 'express').'",订单号为'.$param['exp_sn'];
             (new OrderLogs)->add($curOrder['id'], $note);
             $this->dealCache('order', [$curOrder['id']]);
             return apiReturn('',1,'发货操作成功！');
         }else{
             return apiReturn('',0,'网络错误！稍后再试！');
         }
     }

     /**
      * 改价
      * @return file|json
      */
     public function updeta_price($param)
     {
         $curr = $this->getAOrder($param['order_id']);
         $data = ['order_amount' => $param['price']];
         $note = '改价为'.$param['price'].'元，原因:'.$param['info'];
         (new OrderLogs)->add($curr['id'], $note);
         if($this->save($data,['id' => $curr['id']])){
             $this->dealCache('order', [$curr['id']]);
             return apiReturn('',1,'改价成功！');
         }else {
             return apiReturn('',0,'网络错误，请稍后再试！');
         }
     }

     /**
      * 批量选中变更状态
      * @return file|json
      */
     public function checked_action($param)
     {
         $data = [
             'queren'   => 1,
             'peihuo'   => 2,
             'fahuo'    => 3,
             'qianshou' => 5,
             'tuihuo'   => 7,
             'quxiao'   => 9,
             'wancheng'  => 10,
             'shanchu'   => 100,
         ];
         $status = getval($data, $param['action']) ? $data[$param['action']] : '';
         if(!$status){
             return apiReturn('',0,'参数错误!');
         }
         $loadData = [];
         if(is_array($param['checked'])){
             $loadData = $param['checked'];
         }elseif(is_string($param['checked'])){
             $loadData = explode(",",$param['checked']);
         }
         if($status != 100){
             $result = $this->order_stat($loadData, $status, $param);
             return $result;
         }else{
             $deal = 0;
             foreach ($loadData as $key => $id) {
                 $note = $param['info'] ? '订单'.variable($status, 'order_status').$param['info'] : '订单'.variable($status, 'order_status');
                 (new OrderLogs)->add($id, $note);
                 if(self::destroy($id)){
                     $deal += 1;
                 }
             }
             $this->dealCache('order', $loadData);
             return apiReturn('',1,$deal.'条数据处理成功！失败'.(count($loadData) - $deal).'条！');
         }
     }


     /**
      * 删除至回收站或物理关联删除订单信息
      * @return file|json
      */
     public function del($param)
     {
         //联表删
         $id = $param['id'];
         $this->dealCache('order', [$id]);
         if(getval($param,'softdel') && $param['softdel'] == 'true'){
             return $this->dealClear($id);
         }else{
             $order = self::find($id);
             $del = $order->destroy($id);
             if($del){
                 return apiReturn('',1,'删除订单【'.$order['order_sn'].'】成功！');
             }else{
                 return apiReturn('',0,'删除订单【'.$order['order_sn'].'】失败！');
             }
         }
     }

     /**
      * 删除订单内的商品
      * @return file|json
      */
     public function del_goods($param)
     {
         $detailModel = new OrderDetail;
         // 删除
         $current = $detailModel->where('id',$param['id'])->find();
         $curAll = $detailModel->where('order_id',$current['order_id'])->count();
         if($curAll <= 1){
             return apiReturn('',0,'无法删除订单唯一商品！');
         }
         // 重新计算订单金额
         if(!$current){
             return apiReturn('',0,'参数错误，请核对信息后再试！');
         }
         $allDetail = $detailModel->field('id,order_id,price')->where('order_id',$current['order_id'])->select();
         $tmpTotal = 0;
         foreach ($allDetail as $k => $v) {
             if($v['id'] != $current['id']){
                 $tmpTotal += $v['price'];
             }
         }
         // 启动事务
         Db::startTrans();
         try {
             $order = $this->save(['order_amount' => $tmpTotal],['id' => $current['order_id']]);
             $detail = $detailModel->where('id', $current['id'])->delete();
             // 提交事务
             Db::commit();
         } catch (\Exception $e) {
             // 回滚事务
             Db::rollback();
         }
         $note = '删除订单的商品'.$current['name'].$current['num'].'件，价值'.$current['price'].'元';
         if($order && $detail){
             (new OrderLogs)->add($current['order_id'],$note);
             $this->dealCache('order', [$param['id']]);
             return apiReturn('',1,'删除成功！');
         }else {
             return apiReturn('',0,'网络错误，请稍后再试！');
         }
     }

     /**
      * 处理变更收货人信息
      * @return file|json
      */
     public function deal_address($param)
     {
         $id = $param['id'];
         $currentOrder = $this->where('id', $id)->find();
         if(!$currentOrder){
             return apiReturn('',0,'参数错误，请核对信息后再试！');
         }
         // 日志内容！
         $log = '变更：';
         if($currentOrder['consignee'] != $param['consignee']){
             $log .= '收货人由"'.$currentOrder['consignee'].'"更新至"'.$param['consignee'].'",';
         }
         if($currentOrder['phone'] != $param['phone']){
             $log .= '收货电话由"'.$currentOrder['phone'].'"更新至"'.$param['phone'].'",';
         }
         if($currentOrder['area'] != $param['area']){
             $log .= '地区由"'.$currentOrder['area'].'"更新至"'.$param['area'].'",';
         }
         if($currentOrder['address'] != $param['address']){
             $log .= '详情地区由"'.$currentOrder['address'].'"更新至"'.$param['address'].'",';
         }
         if($log != '变更：'){
             (new OrderLogs)->add($id,$log);
             if($this->allowField(true)->save($param, ['id' => $id])){
                 $this->dealCache('order', $id);
                 return apiReturn('',1,'变更成功！');
             }else{
                 return apiReturn('',0,'网络错误，请稍后再试！!');
             }
         }
     }

    /**
    * 处理变更支付方式
    * @return file|json
    */
    public function deal_payway($param)
    {
        $id = $param['id'];
        $currentOrder = $this->where('id', $id)->find();
        if(!$currentOrder){
            return apiReturn('',0,'参数错误，请核对信息后再试！');
        }
        // 日志内容！
        $log = '变更：';
        if($currentOrder['pay_way'] != $param['pay_way']){
             $isPay = variable($currentOrder['pay_way'],'pay_way') ? variable($currentOrder['pay_way'],'pay_way') : '未支付';
             $log .= '支付方式由"'.$isPay.'"更新至"'.variable($param['pay_way'],'pay_way').'",';
         }
        if($log != '变更：'){
            (new OrderLogs)->add($id,$log);
            $upData = ['pay_way' => $param['pay_way']];
            if($this->allowField(true)->save($upData, ['id' => $id])){
                $this->dealCache('order', $id);
                return apiReturn('',1,'变更成功！');
            }else{
                return apiReturn('',0,'网络错误，请稍后再试！!');
            }
        }
    }


    /**
    * 处理变更订单状态
    * @return file|json
    */
    public function order_stat($id, $status, $param = [])
    {
        $dealIds = [];
        if(is_array($id)){
            $dealIds = $id;
        }elseif (is_numeric($id)){
            $dealIds[] = $id;
        }
        if($status == 3 || $status == 7){
            if(!getval($param, 'exp_com') || !getval($param, 'exp_sn')){
                return apiReturn('', '0', '参数错误，请核对信息后再试！');
            }
            if($status == 3){
                $data = ['exp_sn' => $param['exp_sn'], 'exp_com' => $param['exp_com'], 'order_status' => $status];
                $note = '订单'.variable($status, 'order_status');
            }else{
                $data = ['order_status' => $status];
                $note = '订单'.variable($status, 'order_status').'，退货单号：'.$param['exp_sn'].'物流公司'.variable($param['exp_com'], 'express');
            }
        }else if($status == 9){
            if(!getval($param, 'info')){
                return apiReturn('', '0', '参数错误，请核对信息后再试！');
            }
            $data = ['order_status' => $status];
            $note = '订单取消中，原因是:'.$param['info'];
        }else{
            if($status == 10){
                $pointMod = new MemberPoint;
                foreach ($dealIds as $key => $id) {
                    // 积分生效
                    $where = [];
                    $where[] = ['source', 'eq', $id];
                    $where[] = ['type','eq','order'];
                    $cuData['status'] = 1;
                    $curr = $pointMod->where($where)->find();
                    $pointMod->save($cuData, ['id' => $curr['id']]);
                }
            }
            $data = ['order_status' => $status];
            $note = '订单'.variable($status, 'order_status');
        }
        $result = [];
        foreach ($dealIds as $key => $value) {
            (new OrderLogs)->add($value, $note);
            $data['id'] = $value;
            $result[] = $data;
        }
        $this->dealCache('order', $dealIds);
        if($this->saveAll($result)){
            return apiReturn('',1,variable($status, 'order_status').'成功！');
        }else{
            return apiReturn('',0,'网络错误！稍后再试！');
        }
    }

    //请缓存功能，方便外部调用
    public function cache_clear($order_sn){
        $id = is_numeric($order_sn) ? $order_sn : $this->getAOrder($order_sn,'id');
        $cache_id='order_'.$id;
        cache($cache_id, NULL);
    }

    public function changeNum($data)
    {
    	// 验证数据没有被篡改
    	$current = $this->getAOrder($data['order_sn']);
    	if(!$current){
    		return apiReturn('', 0, '订单没有查到！请核对订单信息');
    	}
    	if($current['pay_status'] == '1'){
    		return apiReturn('', 0, '订单已支付，无法对订单进行变更！');
    	}
    	// 重新计算
    	// 获取订单详情里的
    	if(!is_numeric($data['order_detail']) || !is_numeric($data['order_detail'])){
    		return apiReturn('', 0, '您提交的数据有误！请按照流程操作');
    	}
    	// 计算订单总价
    	$allDetail = (new OrderDetail)->where('order_id', $current['id'])->select();
    	$tmpCount = 0;
    	foreach ($allDetail as $key => $value) {
    		if($value['id'] == $data['order_detail']){
    			$tmpCount += $data['num'] * $value['price'];
    		}else{
    			$tmpCount += $value['num'] * $value['price'];
    		}
    	}
    	// 启动事务
    	Db::startTrans();
    	try {
    		Db::name('order_detail')->where('id', $data['order_detail'])->update(['num' => $data['num']]);
    		Db::name('order')->where('id', $current['id'])->update(['order_amount' => (int)$tmpCount]);
    		// 提交事务
    		Db::commit();
    		$this->cache_clear($current['id']);
    		return apiReturn('', 1, '处理成功！');
    	} catch (\Exception $e) {
    		// 回滚事务
    		Db::rollback();
    		return apiReturn('', 0, '网络错误！请稍后再试！');
    	}
    }


    //
    public function exchange_order($target_id, $order_sn, $userinfo = '')
    {
        if(!$target_id || !$order_sn){
			return apiReturn('',0,'参数错误！');
		}
		// 检测当前订单是否合法
		$order = $this->getAOrder($order_sn);
        $userid = '';
        $curMod=request()->module();
        $actType= $curMod == 'admin' ? '后台' : '前台';
        if($curMod != 'admin' && $order['sell_id'] != $userinfo['id']){
            return apiReturn('', 0, '您无权操作当前订单！');
        }
        $myname = '('.$actType.')'.getval($userinfo, 'realname', getval($userinfo,'username'));
        $target_user = userinfo($target_id);
        $target_name = getval($target_user['info'],'realname');
		$note = "订单转接，由【".$myname."】转接给【".$target_name."】";
		$logData = [
			'order_id'	=> $order['id'],
			'userid'	=> $userid,
			'actions'	=> enjson([request()->module(), request()->controller(), request()->action()]),
			'log_note'	=> $note,
			'log_note'	=> $note,
			'log_ip'	=> request()->ip(),
			'log_time'	=> date('Y-m-d H:i:s',time()),
		];
		//start
		// 启动事务
		Db::startTrans();
		try {
			Db::name('order')->where('id', $order['id'])->data(['sell_id' => $target_id])->update();
			Db::name('order_logs')->data($logData)->insert();
			// 提交事务
			Db::commit();
			cache('order_'.$order['id'],null);
			return apiReturn('', 1, '转接成功！');
		} catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
			return apiReturn('', 0, '转接失败~，稍后再试~');
		}
    }
}
