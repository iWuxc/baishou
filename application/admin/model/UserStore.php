<?php
/**
 * 门店模型类
 * Author: iWuxc
 * Date: 2017-11-30
 */

namespace app\admin\model;
use think\Model;
use think\Db;

class UserStore extends Model{

    protected $rootPath;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this -> rootPath = $_SERVER['DOCUMENT_ROOT'];
    }
    /**
     * 插入图集
     * @param $store_id
     */
    public function afterSave($store_id)
    {
        // 门店相册  图册
        $goods_images = I('goods_images/a');
        if(count($goods_images) > 1)
        {
            array_pop($goods_images); // 弹出最后一个
            $goodsImagesArr = M('StoreImages')->where("store_id = $store_id")->getField('img_id,image_url'); // 查出所有已经存在的图片
            // 删除图片
            foreach($goodsImagesArr as $key => $val)
            {
                if(!in_array($val, $goods_images)){
                    M('StoreImages')->where("img_id = {$key}")->delete();
                    qiniuimgdelv($val);
                }
            }
            // 添加图片
            foreach($goods_images as $key => $val)
            {
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    //更新上传到七牛云的url
                    $url = qiniuuploadv($this->rootPath.$val);
                    $data = array(
                        'store_id' => $store_id,
                        'image_url' => $url,
                    );
                    M("StoreImages")->insert($data);
                    //删除本图集
                    @unlink($this->rootPath.$val);
                }
            }
        }
    }
}