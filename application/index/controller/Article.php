<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 15:03
 */

namespace app\index\controller;


use think\Controller;
use think\Db;

class Article extends Controller
{
    /**
     * 文章详情
     */
    public function detail(){
        $id = input('get.id');
        $info = Db::name('article')->where('id',$id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }
    public function agreement(){
        $info = Db::name('article')->where('id',5)->find();
        $this->assign('info',$info);
        $this->assign('state',input('get.state'));
        return $this->fetch();
    }
}