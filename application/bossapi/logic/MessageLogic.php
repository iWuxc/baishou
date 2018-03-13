<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Message;
use think\Config;
use think\Db;

class MessageLogic extends BaseLogic {

    protected $model;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Message();
    }

    /**
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        check_param('admin_id');
        $list = Config::get('message');
        foreach ($list as $key=>$val){
            $list[$key]['img'] = get_url().$val['img'];
            $where = ['type'=>$val['app_id'],'admin_id'=>$request['admin_id'],'is_del'=>1];
            $row = $this->model->where($where)
                ->field('add_time,title,content,status,add_time')
                ->order('status asc')
                ->find();
            if($val['app_id'] == 1){
                $user_name = unserialize($row['content']);
                $list[$key]['value'] = $user_name['user_name'] ? : '';
            }elseif($val['app_id'] == 2){
                $user_name = unserialize($row['content']);
                $list[$key]['value'] = $user_name['user_name'] ? : '';
            }else{
                $list[$key]['value'] = $row['title'] ? : '';
            }
            $list[$key]['status'] = $row['status'] ? : 1;
            $list[$key]['add_time'] = empty($row) ? '' : date('Y-m-d H:i:s',$row['add_time']);
            $where_ = ['type'=>$val['app_id'],'admin_id'=>$request['admin_id'],'is_del'=>1,'status'=>0];
            $list[$key]['count'] = $this->model->where($where_)->count();
        }
        return $list;
    }


    /**
     * @param array $request
     * @return mixed
     */
    public function getLists($request = array()){
        check_param('app_id,admin_id');
        $param['where']['type'] = $request['app_id'];
        $param['where']['is_del'] = 1;
//        if($request['role_id'] == 4){
//            $param['where']['admin_id'] = $request['admin_id'];
//        }
        $param['where']['admin_id'] = $request['admin_id'];
        $param['parameter'] = $request;
        $param['order'] = 'add_time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            $where = ['type'=>$request['app_id'],'status'=>0,'admin_id'=>$request['admin_id']];
            $this->model->where($where)->update(['status'=>1]);
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['content'] = unserialize($val['content']);
                $list['list'][$key]['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        return $list['list'];
    }

    /**
     * 评估师评估报告
     * @param array $request
     * @return array
     */
    public function estimateMessage($request = array()){
        check_param('admin_id');
        $param['where']['type'] = 9;
        $param['parameter'] = $request;
        $param['order'] = 'add_time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            $this->model->where(['type'=>9,'status'=>0,'admin_id'=>$request['admin_id']])->update(['status'=>1]);
            foreach ($list['list'] as $key=>$val){
                $content = unserialize($val['content']);
                $content['pawn_name'] = Db::name('pawn')->where('pawn_id',$content['pawn_id'])->value('pawn_name') ? : '';
                $content['store_name'] = Db::name('user_store')->where('store_id',$content['store_id'])->value('store_name') ? : '';
                $list['list'][$key]['content'] = $content;
                $list['list'][$key]['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        return $list['list'];
    }

    /**
     * 评估师最新消息
     * @param array $request
     */
    public function estimateFind($request = array()){
        check_param('admin_id');
        $row = $this->model->where(['type'=>9,'admin_id'=>$request['admin_id']])->order('add_time desc')->field('title,status,add_time')->find();
        $row['add_time'] = date('Y-m-d H:i:s',$row['add_time']);
        $data = array(
            'count' => $this->model->where(['type'=>9,'status'=>0,'admin_id'=>$request['admin_id']])->count(),
            'row'   => $row
        );
        if(!empty($row)){
            api_response('1','',$data);
        }else{
            api_response_('1','');
        }
    }

}