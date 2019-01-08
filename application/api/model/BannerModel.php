<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 14:31
 */

namespace app\api\model;


use think\Model;

class BannerModel extends Model
{
    protected $name='banner';

    /**
     * 首页轮播图展示
     */
    public function RotationChart(){
        $url = GetDomainName().'/uploads/images/';
        $lists = $this->field("id,CONCAT('".$url."',images) as path")->where('ad_position_id',1)->limit(5)->order('id DESC')->select();
        return $lists;
    }
    /**
     * app 首页中间展示图片
     */
    public function getAppMedioImg(){
        $url = GetDomainName().'/uploads/images/';
        $list = $this->field("id,CONCAT('".$url."',images) as path")->where('ad_position_id',2)->order('id DESC')->find();
        return $list['path'];
    }
}