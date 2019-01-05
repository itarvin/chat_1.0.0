<?php
// +----------------------------------------------------------------------
// | Api
// +----------------------------------------------------------------------
namespace app\api\controller;
use think\Controller;

class Api extends Controller{

	// 上传唯一入口
	public function upload(){
		$param = $this->request->param();
		$path = 'layim';
		$param['size'] = isset($param['size']) ? $param['size'] : '' ;
		//替换的图片网址
		$replace = isset($param['replace']) ? $param['replace'] : '';
		return json(\tools\Upload::upload($path,$param['size'],$replace));
	}
}
