<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28
 * Time: 17:06
 */

namespace app\common\model;


use think\Model;

class MoneyRegular extends Model
{
    protected $name='';

    /**
     * 查询定期记录
     * @param $field
     * @param $map
     * @param $page
     * @param $row
     * @param $order
     */
    public function getRegularLists($field,$map,$page,$row,$order){
        $count = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid=r.uuid')->count();
        $lists = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid=r.uuid')->page($page,$row)->order($order)->select();
        return ['count'=>$count,'lists'=>$lists];
    }

    /**
     * 查询单个用户的统计信息
     * @param $uuid
     */
    public function getMemberCount($uuid){
        $count['state_all'] = $this->where('uuid',$uuid)->sum('number');
        $count['state_0'] = $this->where(['uuid'=>$uuid,'state'=>0])->sum('surplus');
        $count['state_1'] = $this->where(['uuid'=>$uuid,'state'=>1])->sum('number');
        return $count;
    }
    /**
     * 查询统计
     */
    public function getRegularCount(){
        $count['state_all'] = $this->count();
        $count['state_all_sum'] = $this->sum('number');
        $count['state_0'] = $this->where('state',0)->count();
        $count['state_0_sum'] = $this->where('state',0)->sum('surplus');
        $count['state_1'] = $this->where('state',1)->count();
        $count['state_1_sum'] = $this->where('state',1)->sum('number');
        return $count;
    }
    /**
     * 查询订单总数
     * @param $map
     */
    public function getOrderCount($map){
        return $this->where($map)->count();
    }
}