<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 14:36
 */

namespace app\api\model;


use think\Model;

class ArticleModel extends Model
{
    protected $name='article';

    public function HomePage(){
        $url = GetDomainName().'/index/article/detail.html?id=';
        $lists = $this->field("id,title,CONCAT('".$url."',id) as path")->where('cate_id',1)->limit(3)->order('id DESC')->select();
        return $lists;
    }

    /**
     * 分类查询文章列表
     * @param $type
     * @param $page
     * @param $row
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getArticleLists($type,$page,$row){
        $url = GetDomainName().'/index/article/detail.html?id=';
        $lists = $this->field("id,title,CONCAT('".$url."',id) as path,create_time")
            ->where('cate_id',$type)->where('cate_id',$type)->page($page,$row)->order('id DESC')->select();
        return $lists;
    }
        /**
     * 分类查询文章列表
     * @param $type
     * @param $page
     * @param $row
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getNewsLists($type,$page,$row){
        $url = GetDomainName().'/index/article/detail.html?id=';
        $img_url = GetDomainName().'/uploads/images/';
        $lists = $this->field("id,title,CONCAT('".$url."',id) as path,create_time,CONCAT('".$img_url."',photo) as img_path,remark")
            ->where('cate_id',$type)->where('cate_id',$type)->page($page,$row)->order('id DESC')->select();
        return $lists;
    }
}