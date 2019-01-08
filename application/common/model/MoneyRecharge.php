<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28
 * Time: 10:39
 */

namespace app\common\model;


use think\Model;

class MoneyRecharge extends Model
{
    protected $name='money_recharge';

    /**
     * 查询充值记录
     * @param $field
     * @param $map
     * @param $page
     * @param $row
     * @param $order
     */
    public function getRechargeList($field,$map,$page,$row,$order){
        //查询数据总数
        $count = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid = r.uuid')->count();
        //查询数据列表
        $lists = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid = r.uuid')->page($page,$row)->order($order)->select();
        return ['count'=>$count,'lists'=>$lists];
    }

    /**
     * 查询单条记录
     * @param $field
     * @param $id
     */
    public function getRechargeFind($field,$id){
        $info = $this->field($field)->where('id',$id)->find();
        return $info;
    }
    /**
     * 驳回充值申请
     * @param $id
     * @param $info
     * @param $admin_id
     * @param $admin_name
     */
    public function getRechargeReject($id,$info,$admin_id,$admin_name){
        $this->startTrans();
        try{
            $this->where('id',$id)->update(['state'=>2,'info'=>$info,'admin_id'=>$admin_id,'admin_name'=>$admin_name,'end_time'=>time()]);
            $this->commit();
            return ['code'=>1011,'msg'=>'驳回充值申请成功'];
        }catch (\PDOException $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>'驳回充值申请失败，'.$exception->getMessage()];
        }
    }
    /**
     * 充值确认
     * @param $id
     * @param $admin_id
     * @param $admin_name
     */
    public function getRechargeConfirm($id,$admin_id,$admin_name){
        $this->startTrans();
        try{
            $this->where('id',$id)->update(['state'=>1,'admin_id'=>$admin_id,'admin_name'=>$admin_name,'end_time'=>time()]);
            $this->commit();
            return ['code'=>1011,'msg'=>'确认充值申请成功'];
        }catch (\PDOException $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>'确认充值申请失败，'.$exception->getMessage()];
        }
    }

    /**
     * 充值申请记录总数
     */
    public function getRechargeCount(){
        $state['state_all'] = $this->count();
        $state['state_0'] = $this->where('state','0')->count();
        $state['state_1'] = $this->where('state','1')->count();
        $state['state_2'] = $this->where('state','2')->count();
        $state['state_all_sum'] = $this->sum('number');
        $state['state_0_sum'] = $this->where('state',0)->sum('number');
        $state['state_1_sum'] = $this->where('state',1)->sum('number');
        $state['state_2_sum'] = $this->where('state',2)->sum('number');
        return $state;
    }

    /**
     * 充值三个类型总钱数
     */
    public function getRechargeSum(){
        //今日总申请
        $sum['today_state_0']   = $this->whereTime('add_time','today')->sum('number');
        //查询今日完成
        $sum['today_state_1'] = $this->where('state',1)->whereTime('add_time','today')->sum('number');
        //查询今日驳回
        $sum['today_state_2'] = $this->where('state',2)->whereTime('add_time','today')->sum('number');
        return $sum;
    }
}