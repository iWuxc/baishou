<?php
/**
 * 七牛云对象存储
 */
namespace app\admin\controller;

class QiniuOs extends Base
{
    //引入七牛云存储SDK
    Vendor('php-sdk-master.autoload');
    use Qiniu\Auth;
    use Qiniu\Storage\BucketManager;
    use Qiniu\Storage\UploadManager;
    /**
     * [qiniuuploadv 七牛云上传门店头像等图片]
     * @param  [type] $tmp_name [本地图片url地址]
     * @return [type]           [description]
     */
    public function qiniuuploadv($tmp_name)
    {
        if (!file_exists($tmp_name)) {
            return false;
        }
        // 要上传图片的本地路径
        $filePath = $tmp_name;
        $ext = pathinfo($tmp_name, PATHINFO_EXTENSION);
        // 上传到七牛后保存的文件名
        $key =substr(md5($tmp_name) , 0, 5). date('YmdHis') . rand(0, 9999) .'.'. $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = C('ACCESSKEYV');
        $secretKey = C('SECRETKEYV');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = C('BUCKETV');
        $domain = C('DOMAINV');
        $token = $auth->uploadToken($bucket);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        $res = $uploadMgr->putFile($token, $key, $filePath);
        if (!$res) {
            return false;
        } else {
            $url = 'http://'.$domain .'/'. $key;
            return $url;
        } 
    }
    /**
     * [qiniuimgdelv 七牛云删除门店头像等图片]
     * @param  [type] $url [七牛云图片url地址]
     * @return [type]           [description]
     */
    function qiniuimgdelv($url)
    {
        if (!file_exists($url)) {
                return false;
        }
        $new_url = substr(strrchr($url,'/'),1);
        $accessKey = C('ACCESSKEYV');
        $secretKey = C('SECRETKEYV');
        $bucket = C('BUCKETV');
        $key = $new_url;
        $auth = new Auth($accessKey, $secretKey);
        $config = new Qiniu\Config();
        $bucketManager = new Qiniu\Storage\BucketManager($auth, $config);
        $err = $bucketManager->delete($bucket, $key);
        if ($err) {
            return false;
        }     
        return true;
    }
    /**
     * [qiniuupload 七牛云上传家具图片]
     * @param  [type] $tmp_name [本地图片url地址]
     * @return [type]           [description]
     */
    function qiniuupload($tmp_name)
    {
        if (!file_exists($tmp_name)) {
            return false;
        }
        // 要上传图片的本地路径
        $filePath = $tmp_name;
        $ext = pathinfo($tmp_name, PATHINFO_EXTENSION);
        // 上传到七牛后保存的文件名
        $key =substr(md5($tmp_name) , 0, 5). date('YmdHis') . rand(0, 9999) .'.'. $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = C('ACCESSKEY');
        $secretKey = C('SECRETKEY');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = C('BUCKET');
        $domain = C('DOMAIN');
        $token = $auth->uploadToken($bucket);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        $res = $uploadMgr->putFile($token, $key, $filePath);
        if (!$res) {
            return false;
        } else {
            $url = 'http://'.$domain .'/'. $key;
            return $url;
        } 
    }
    /**
     * [qiniuimgdel 七牛云删除家具图片]
     * @param  [type] $url [七牛云图片url地址]
     * @return [type]           [description]
     */
    function qiniuimgdel($url)
    {
        if (!file_exists($url)) {
                return false;
        }
        $new_url = substr(strrchr($url,'/'),1);
        $accessKey = C('ACCESSKEY');
        $secretKey = C('SECRETKEY');
        $bucket = C('BUCKET');
        $key = $new_url;
        $auth = new Auth($accessKey, $secretKey);
        $config = new Qiniu\Config();
        $bucketManager = new Qiniu\Storage\BucketManager($auth, $config);
        $err = $bucketManager->delete($bucket, $key);
        if ($err) {
            return false;
            
        }     
        return true;
    }
    
}