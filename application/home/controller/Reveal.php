<?php
namespace app\home\controller;
use think\Controller;
use think\Db;
/**
 * 应用场景：主页
 * @since   2018/06/20 创建
 * @author  itarvin itarvin@gmail.com
 */
class Reveal extends Controller
{
    protected $param;
    protected $title;
    protected $loadData;
    protected function initialize(){
        $this->param = $this->request->param();
        $this->loadData = [
            '0117abad35bd0fdd'  => 'i_sun',
            '7a7d406f015bbf1e'  => 'i_tencent',
            '4e90fedd7ca8c5c1'  => 'i_hakeinfo',
            '43155ba86666d29d'  => 'i_meiwen',
        ];
    }

    /**
     * 应用场景：前台主页
     * @return view
     */
    public function index()
    {
        // 获取数据字段
        $field = [];
        $param = isset($this->param['sign']) ? $this->param['sign'] : '4e90fedd7ca8c5c1';
        $curent = $this->loadData[$param];
        $sql_str = "SHOW COLUMNS FROM ".$curent;
        $rs = Db::connect('db_config1')->table($curent)->query($sql_str);
        foreach ($rs as $key => $value) {
            $field[] = $value['Field'];
        }
        // 获取数据
        $list = Db::connect('db_config1')->table($curent)->order('id','desc')->paginate();
        $count = Db::connect('db_config1')->table($curent)->count();
        $this->assign([
            'list' => $list,
            'field' => $field,
            'count' => $count
        ]);
        return $this->fetch();
    }
}
