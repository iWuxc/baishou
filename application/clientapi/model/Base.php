<?php
/**
 * 用户基类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\model;
use think\Model;
use think\Db;
use think\Page;

class Base extends Model {

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}