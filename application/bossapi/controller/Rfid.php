<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\StoreLogic;
use app\bossapi\logic\RfidLogic;
use think\Db;

class Rfid extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new RfidLogic();
    }

    /**
     * 监控视频
     */
    public function video(){
        $logic = new StoreLogic();
        $logic->video($_REQUEST);
    }

    /**
     * rfid故障列表
     */
    public function rfidList(){
        $list = $this->logic->getList($_REQUEST,1);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 设备故障
     */
    public function rfidError(){
        $list = $this->logic->getList($_REQUEST,2);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 获取广东省下的城市
     */
    public function region(){
        $list = Db::name('region')->where(['parent_id'=>28240])->field('id city_id, name city_name')->select();
        api_response('1','',$list);
    }

}