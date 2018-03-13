<?php
/**
 * 门店模型类
 * Author: iWuxc
 * time 2017-12-24
 */
namespace app\clientapi\model;

use think\Db;
use think\Page;

class UserStore extends Base{

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * 获取门店列表
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreList($param = array()){
        $param['page_size'] = 10;
        $count =  $this -> where($param['where']) -> count();
        $Page = new Page($count,$param['page_size'],$param['p']);
        $page_show = $Page->show();
        $list = $this ->field('store_id,store_name,province,city,district,address')
            ->where($param['where'])
            ->order($param['order'])
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        return array('list'=>$list,'page'=>$page_show,'page_total'=>$Page->totalRows);
    }

    /**
     * 获取门店详情
     * @param $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($store_id){
        $result = $this ->field('store_id,store_name,user_name,store_mobile,opening_hours,closeing_hours,main_pro,province,city,district,address')
            ->where('store_id',$store_id) -> find() -> getData();
        if($result){
            //获取图集
            $images = Db::name('store_images') -> field('image_url') -> where('store_id',$store_id) -> select();
            if(!empty($images)){
                foreach($images as $key => $val){
                    $result['store_images'][] = get_url().$val['image_url'];
                }
            }
        }
        return $result;
    }

}