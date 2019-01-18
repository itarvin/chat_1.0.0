<?php
// +----------------------------------------------------------------------
// | Author: itarvin <chnitarvin@gmail.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\common\model\Category as cateMod;
/**
 * 商品
 */
class Category extends Base{

    protected $cateArr = ['笔记' => 'notes', '商品' => 'goods','文章' => 'article'];

    /**
     * 分类列表
     * @return view
     */
    public function index()
    {
        $cateMod = new cateMod;
        foreach ($this->cateArr as $key => $module) {
            $data[$module] = $cateMod->getTree($module);
        }
        return $this->fetch('',['data' => $data,'module' => $this->cateArr]);
    }

    /**
     * 添加分类
     * @return view
     */
    public function add()
    {
        $cateMod = new cateMod;
        if($this->request->isAjax()){
            if(getval($this->param, 'class')){
                $re = $cateMod->getTree($this->param['class']);
                return apiReturn($re,1,'获取成功!');
            }else{
                return apiReturn('',0,'参数错误，请核对后再试！');
            }
        }
        if($this->request->isPost()){
			$result = $cateMod->store($this->param);
            return $this->buildReturn($result);
        }
        if(!getval($this->param, 'cate')){
            return $this->buildReturn(apiReturn('', 0, '参数错误，请核对后再试！'));
        }
        $this->assign([
            'cate'      => $this->param['cate'],
            'module'    => $this->cateArr
        ]);
        return $this->fetch('');
    }

    /**
     * 更新分类
     * @return view
     */
    public function edit()
    {
        $cateMod = new cateMod;
        $param = $this->param;
        if($this->request->isAjax()){
            if(getval($param, 'class')){
                $re = $cateMod->getTree($param['class']);
                return apiReturn($re,1,'获取成功!');
            }else{
                return apiReturn('',0,'参数错误，请核对后再试！');
            }
        }
        if($this->request->isPost()){
			$result = $cateMod->store($param);
            return $this->buildReturn($result, url('category/index'));
        }
        // 返回对应数据
        if(!getval($param, 'id')){
            return $this->buildReturn(apiReturn('',0, '参数错误，请核对后再试！'),url('category/index'));
        }
        $data = $cateMod->getInfo($param['id']);
        $class = $cateMod->getTree($data['module']);
        return $this->fetch('',['data' => $data, 'class' => $class,'module' => $this->cateArr]);
    }

    /**
     * 删除分类
     * @return json
     */
    public function delete()
    {
        $cateMod = new cateMod;
        $param = $this->param;
        // 返回对应数据
        if(!getval($param, 'id')){
            return $this->buildReturn(apiReturn('',0, '参数错误，请核对后再试！'),url('category/index'));
        }
        $result = $cateMod->del($param['id']);
        return $this->buildReturn($result);
    }
}
