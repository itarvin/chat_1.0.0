<?php
namespace app\home\controller;
use app\index\model\Article;
use app\index\model\Category as cateMod;
use app\index\model\Admin;
use think\Controller;
/**
 * 应用场景：主页
 * @since   2018/06/20 创建
 * @author  itarvin itarvin@gmail.com
 */
class Index extends Controller
{

    protected $cateIds;
    protected $param;
    protected $title;
    protected function initialize(){
        $cateAll = (new cateMod)->getList(['type' => 'top'], 0, 'id desc', 'id');
        foreach ($cateAll as $k => $v) {
            $this->cateIds[] = $v['id'];
        }
        $this->param = $this->request->param();
    }

    /**
     * 应用场景：前台主页
     * @return view
     */
    public function index()
    {
        $articleMod = new Article;
        $result = [];
        $keyword = getval($this->param, 'keyboard', '');
        if($keyword && $this->request->isPost()){
            $where[] = ['title|keywords|content', 'like', '%'.$keyword.'%'];
            $where[] = ['cateid' , 'in', $this->cateIds];
            $result = $articleMod->getList($where, '0', "id desc", "id,title,picture");
        }
        $cateData = (new cateMod)->getList(['type' => 'top'], 6, 'id desc', 'id,name');
        foreach($cateData as $k => $v){
            $field = 'id,title,createtime';
            $cateData[$k]['article'] = $articleMod->getList(['cateid' => $v['id']], 8, 'id desc', $field);
        }
        // 推荐
        $recommend = $articleMod->getList([['cateid', 'in', $this->cateIds]], 15, 'hits desc', 'id,title,picture');
        $this->title = 'itarvin博客';
        $this->assign([
            'data'      => $cateData,
            'recommend' => $recommend,
            'title'     => $this->title,
            'result'    => $result
        ]);
        return $this->fetch('');
    }

    /**
     * 应用场景：前台主页
     * @return view
     */
    public function list()
    {
        $param = $this->param;
        $cateId = isset($param['id']) && is_numeric($param['id']) ? $param['id'] : '';
        if(!$cateId && !in_array($cateId, $this->cateIds)){
            $this->error('平生最讨厌不按套路来的人哦！',url('index/index'));
        }
        $cur = (new cateMod)->getInfo($param['id']);
        $field = 'id,title,createtime,picture,keywords,authorid,hits,laud,cateid';
        $data = (new Article)->pages($param, 1, 'id desc', $field);
        // 推荐
        $cateData = (new cateMod)->getList(['type' => 'top'], 3, 'id desc', 'id,name');
        foreach($cateData as $k => $v){
            $field = 'id,title,createtime';
            $cateData[$k]['article'] = (new Article)->getList(['cateid' => $v['id']], 8, 'id desc', $field);
        }
        $this->title = ($cur != '') ? $cur['name'] : '慢生活';
        $this->assign([
            'data'      => $data,
            'current'   => $current,
            'cateData'  => $cateData,
            'title'     => $this->title,
        ]);
        return $this->fetch();
    }

    /**
     * 应用场景：详情
     * @return view
     */
    public function show()
    {
        $param = $this->param;
        $articleMod = new Article;
        // 浏览自增1
        $articleMod->where('id', $param['id'])->setInc('hits');
        $data = $articleMod->getInfo($param['id']);
        $data['content'] = bucketSrc($data['content']);
        $data['cate_name'] = (new cateMod)->getInfo($data['cateid'],'name');
        $data['username'] = (new Admin)->getInfo($data['authorid'],'username');
        // 上一篇
        $front = $articleMod->field('id,title')->where("id <".$param['id'])->order('id desc')->limit('1')->find();
        //下一篇
        $after = $articleMod->field('id,title')->where("id >".$param['id'])->order('id desc')->limit('1')->find();
        // 相关文章
        $field = 'id,createtime,title,picture';
        $conform = $articleMod->getList([['cateid', 'eq', $data['cateid']]], 4, "createtime desc", $field);
        // 排出最近10篇文章
        $lists = $articleMod->getList([['cateid', 'in', $this->cateIds]], 30, "hits desc", "id,createtime,title");
        $this->title = ($data['title'] != '') ? $data['title'] : 'itarvin文章';
        $this->assign([
            'data'      => $data,
            'front'     => $front,
            'after'     => $after,
            'conform'   => $conform,
            'lists'     => $lists,
            'title'     => $this->title,
        ]);
        return $this->fetch();
    }
}
