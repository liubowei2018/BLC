<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28
 * Time: 9:34
 */

namespace app\common\model;


use think\Model;

class MemberModel extends Model
{
    protected $name='member';
    //查询用户总量
    public function getUserCount($map=[],$time=''){
        if(!$time){
            $count = $this->where($map)->count();
        }else{
            $count = $this->whereTime('create_time',$time)->count();
        }
        return $count;
    }
}