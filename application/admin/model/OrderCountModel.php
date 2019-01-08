<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/4
 * Time: 13:57
 */

namespace app\admin\model;


use think\Model;

class OrderCountModel extends Model
{
    protected $name='order_count';
    /**
     * 返回前七天列表
     */
    public function getDayliat(){
       $list = $this->field('complete,found,show_time')->order('add_time DESC')->limit(6)->select()->toArray();
       foreach ($list as $k=>$v){
           $list[$k]['show_time']=date('m-d',($v['show_time']-24*60*60));
       }
        krsort($list);
        $complete = [];
        $found = [];
        $show_time = [];
        foreach ($list as $a=>$b){
            $complete[]= $b['complete'];
            $found[]= $b['found'];
            $show_time[]= $b['show_time'];
        };
        return ['complete'=>$complete,'found'=>$found,'show_time'=>$show_time];
    }
}