<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Store;
use app\bossapi\model\StoreMonitoring;
use think\Db;

class StoreLogic extends BaseLogic {

    protected $model;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Store();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        check_param('user_id');
        $param['where']['user_id'] = $request['user_id'];
        $param['parameter'] = $request;
        $param['order'] = 'addtime desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){

            }
        }
        return $list['list'];
    }

    /**
     * 单条数据
     * @param array $request
     * @return array|false|\PDOStatement|string|Model
     */
    public function findRow($request = array()){
        check_param('store_id');
        $param['where']['store_id'] = $request['store_id'];
        $row = $this->model->findRow($param);
        if(!empty($row)){
            $row['established'] = date('Y-m-d',$row['established']);
            $row['rent_begintime'] = empty($row['rent_begintime']) ? '' : date('Y-m-d',$row['rent_begintime']);
            $row['rent_endtime'] = empty($row['rent_endtime']) ? '' : date('Y-m-d',$row['rent_endtime']);
            //获取门店图片列表
            $imgs = Db::name('store_images')->where('store_id',$request['store_id'])->field('img_id id,image_url img')->select();
            if(!empty($imgs)){
                foreach ($imgs as $key=>$val){
                    $imgs[$key]['img'] = get_url().$val['img'];
                }
            }
            $row['imgs'] = empty($imgs) ? array() : $imgs;
        }
        return $row;
    }

    /**
     * @param array $request
     * @return mixed
     */
    public function store($request = array()){
        check_param('admin_id,role_id');
        if($request['role_id'] == 4){
            //获取当前信贷的家具
            $user_ids = Db::name('users')->where(['xd_id'=>$request['admin_id']])->getField('user_id',true);
            if(!empty($user_ids)){
                $store_ids = Db::name('user_store')->where(['user_id'=>['in',array_unique($user_ids)]])->getField('store_id',true);
                $param['where']['store_id'] = ['in',array_unique($store_ids)];
            }else{
                $param['where']['store_id'] = -1;
            }
        }
//        elseif($request['role_id'] == 2){
//            //风控门店列表
//            $user_ids = Db::name('users')->where(['fk_id'=>$request['admin_id']])->getField('user_id',true);
//            if(!empty($user_ids)){
//                $store_ids = Db::name('user_store')->where(['user_id'=>['in',array_unique($user_ids)]])->getField('store_id',true);
//                $param['where']['store_id'] = ['in',array_unique($store_ids)];
//            }else{
//                $param['where']['store_id'] = -1;
//            }
//        }

        if(!empty($request['keywords'])){
            $param['where']['store_name|user_name'] = ['LIKE','%'.$request['keywords'].'%'];
        }
        if(!empty($request['city_id'])){
            $param['where']['city'] = $request['city_id'];
        }
        $param['order'] = 'addtime desc';
        $list = $this->model->getList($param)['list'];
        if(!empty($list)){
            foreach ($list as $key=>$val){
                //默认封面图
                $cover = Db::name('store_images')->where('store_id',$val['store_id'])->value('image_url');
                if(!empty($cover)){
                    $list[$key]['cover'] = get_url().$cover;
                }else{
                    $list[$key]['cover'] = '';
                }
                $list[$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
                //获取当前门店下有故障的摄像头
                $bad = Db::name('store_monitoring')->where(['store_id'=>$val['store_id'],'is_bad'=>1])->value('id');
                if(!empty($bad)){
                    $list[$key]['is_bad'] = 1;
                }else{
                    $list[$key]['is_bad'] = 0;
                }
            }
        }
        return $list;
    }

    /**
     * 门店监控视频
     * @param array $request
     */
    public function video($request = array()){
        check_param('store_id');
        $model = new StoreMonitoring();
        $param['where']['store_id'] = $request['store_id'];
        $param['order'] = 'add_time desc';
        $list = $model->getList($param)['list'];
        if(!empty($list)){
            foreach ($list as $key=>$val){

            }
        }
        $data = [
            'list'      => $list,
            'count_a'   => Db::name('equipment_wrong')->where(['store_id'=>$request['store_id'],'type'=>1,'status'=>0])->count(),
            'count_b'   => Db::name('equipment_wrong')->where(['store_id'=>$request['store_id'],'type'=>2,'status'=>0])->count(),
        ];
        if(!empty($list)){
            api_response('1','',$data);
        }else{
            api_response_('1','暂无数据',(object)array());
        }
    }

}