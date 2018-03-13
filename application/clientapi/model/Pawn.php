<?php
/**
 * 抵押品模型类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;
use think\Page;

class Pawn extends Base{

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /*
     * 获取抵押品列表
     */
    public function getPawnList($where){
        $param['page_size'] = 10;
        /****存在分页的情况下使用****/
        $count = Db::name('pawn') -> where($where['param']) -> count();

        $Page = new Page($count,$param['page_size'],$where['p']);
        $page_show = $Page->show();
        /****存在分页的情况下使用****/
        $list = Db::name('pawn')
            -> field('pawn_id,pawn_no,pawn_name,status,store_name')
            ->where($where['param'])
            ->order($where['order'])
            ->limit($Page->firstRow.','.$Page->listRows)//分页情况下
            ->select();
        //获取家具的一张封面图
        foreach ($list as $key => $val){
            $img = Db::name('pawn_imgs') -> where('pawn_id',$val['pawn_id']) -> value('img_url');
            $list[$key]['image'] = $img ? get_url().$img : '';
        }
        return $list;
    }

    /**
     * 抵押品详情
     * @param $pawn_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($pawn_id){
        $result = Db::name('pawn') -> field('pawn_id,user_id,status,pawn_no,pawn_name,pawn_model,wood_name,pawn_area,wood_auxname,pawn_auxarea,pawn_num,new_value,pawn_cost')
            -> where('pawn_id',$pawn_id)
            -> find();
        //贷款截止时间
        if($result){
            $draw_stptime =  Db::name('credit_conclusion') -> where('user_id',$result['user_id']) -> value('draw_stptime');
            $result['draw_stptime'] = date('Y-m-d', $draw_stptime);
        }
        $result = filter_null($result);
        return $result;
    }

    /*
     * 搜索
     */
    public function search($userid = null, $ids = null, $keyword, $p = 1){
        $where = [];
        $userid && $where['param']['user_id'] = $userid;
        $where['param']['status'] = array('EGT',1);
        $where['order'] = "addtime desc";
        if(!empty($keyword)){
            $where['param']['pawn_name'] = array('like', "%$keyword%");
        }
        if($ids != null){
            $where['param']['store_id'] = ['in',$ids];
        }
        $param['page_size'] = 10;
        /****存在分页的情况下使用****/
        $count = Db::name('pawn') -> where($where['param']) -> count();
        $Page = new Page($count,$param['page_size'],$p);
        $page_show = $Page->show();
        /****存在分页的情况下使用****/
        if($ids == null){
            $list = $this -> field('pawn_id,pawn_no,pawn_name,status,store_name')
                ->where($where['param'])
                ->order($where['order'])
                ->limit($Page->firstRow.','.$Page->listRows)//分页情况下
                ->select();
            //获取家具的一张封面图
            foreach ($list as $key => $val){
                $img = Db::name('pawn_imgs') -> where('pawn_id',$val['pawn_id']) -> value('img_url');
                $list[$key]['image'] = $img ? get_url().$img : '';
            }
        }else{//员工登录
            $list = $this -> field('pawn_id,pawn_no,pawn_name,status')
                ->where($where['param'])
                ->order($where['order'])
                ->limit($Page->firstRow.','.$Page->listRows)//分页情况下
                ->select();
            //获取家具的一张封面图
            foreach ($list as $key => $val){
                $img = Db::name('pawn_imgs') -> where('pawn_id',$val['pawn_id']) -> value('img_url');
                $list[$key]['image'] = $img ? get_url().$img : '';
            }
        }
        return $list;
    }
}