<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28
 * Time: 14:17
 */

namespace app\common\model;


use think\Db;
use think\Model;

class MoneyPropose extends Model
{
    protected $name = 'money_propose';
    /**
     * 查询提现记录
     * @param $field
     * @param $map
     * @param $page
     * @param $row
     * @param $order
     */
    public function getProposeList($field,$map,$page,$row,$order){
        //查询数据总数
        $count = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid = r.uuid')->count();
        //查询数据列表
        $lists = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid = r.uuid')->page($page,$row)->order($order)->select();
        $ConfigCapital= ConfigCapital();
        $poundage_propose = $ConfigCapital['poundage_propose'];
        foreach ($lists as $k=>$v){
            $lists[$k]['poundage'] = $v['number']*$poundage_propose/100;
            $lists[$k]['actual']   = $v['number']-$v['number']*$poundage_propose/100;
        }
        return ['count'=>$count,'lists'=>$lists];
    }
    /**
     * 查询用户单条信息
     * @param $field
     * @param $id
     */
    public function getProposeFind($field,$id){
        $info = $this->field($field)->where('id',$id)->find();
        return $info;
    }
    /**
     * 查询单个用户的统计信息
     * @param $uuid
     */
    public function getMemberCount($uuid){
        $count['state_all'] = $this->where('uuid',$uuid)->sum('number');
        $count['state_0'] = $this->where(['uuid'=>$uuid,'state'=>0])->sum('number');
        $count['state_1'] = $this->where(['uuid'=>$uuid,'state'=>1])->sum('number');
        return $count;
    }
    /**
     * 确认提现申请
     * @param $id
     * @param $admin_id
     * @param $admin_name
     */
    public function getProposeConfirm($id,$admin_id,$admin_name){
        $this->startTrans();
        try{
            $this->where('id',$id)->update(['state'=>1,'admin_id'=>$admin_id,'admin_name'=>$admin_name,'end_time'=>time()]);
            $this->commit();
            return ['code'=>1011,'msg'=>'确认提现申请成功'];
        }catch (\PDOException $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>'确认提现申请失败，'.$exception->getMessage()];
        }
    }

    /**
     * 驳回提现申请
     * @param $id
     * @param $info
     * @param $admin_id
     * @param $admin_name
     */
    public function getProposeReject($id,$info,$admin_id,$admin_name){
        $this->startTrans();
        try{
            $this->where('id',$id)->update(['state'=>2,'info'=>$info,'admin_id'=>$admin_id,'admin_name'=>$admin_name,'end_time'=>time()]);
            $this->commit();
            return ['code'=>1011,'msg'=>'驳回提现申请成功'];
        }catch (\PDOException $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>'驳回提现申请失败，'.$exception->getMessage()];
        }
    }
    /**
     * 充值申请记录总数
     */
    public function getProposeCount(){
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
    public function getProposeSum(){
        //今日总申请
        $sum['today_state_0']   = $this->whereTime('add_time','today')->sum('number');
        //查询今日完成
        $sum['today_state_1'] = $this->where('state',1)->whereTime('add_time','today')->sum('number');
        //查询今日驳回
        $sum['today_state_2'] = $this->where('state',2)->whereTime('add_time','today')->sum('number');
        return $sum;
    }
}