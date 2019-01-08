<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27
 * Time: 13:21
 */

namespace app\api\model;


use think\Model;

/**
 * Class MoneyRegular  定期管理
 * @package app\api\model
 */
class MoneyRegular extends Model
{
    protected $name='money_regular';

    /**
     * 添加定期记录
     * @param $uuid
     * @param $number
     * @param $ratio
     * @param $end_day
     * @param $info
     */
    public function getAddData($uuid,$number,$ratio,$end_day,$info){
        $data = [
            'order_number'=>'D'.time().rand(1000,9999),
            'uuid'=>$uuid,
            'number'=>$number,
            'surplus'=>$number,
            'ratio'=>$ratio,
            'today'=>0,
            'end_day'=>$end_day,
            'state'=>'0',
            'add_time'=>time(),
        ];
        $this->startTrans();
        try{
            $this->insert($data);
            $this->commit();
            return ['code'=>1011,'msg'=>'买入定期成功','data'=>$info];
        }catch (\PDOException $e){
            $this->rollback();
            return ['code'=>1012,'msg'=>$e->getMessage(),'data'=>$info];
        }
    }
    /**
     * 查询定期记录
     * @param $uuid
     * @param $page
     * @param $row
     * @param $field
     */
    public function getRegularLog($uuid,$page,$row,$field='*'){
        $lists = $this->field($field)->where('uuid',$uuid)->page($page,$row)->order('state ASC,add_time ACS')->select();
        return ['code'=>1011,'msg'=>'成功','data'=>$lists];
    }

    /**
     * 查询定期持有量
     * @param $uuid
     */
    public function getRegularCount($uuid){
        $count = $this->where(['uuid'=>$uuid,'state'=>0])->sum('surplus');
        return "$count";
    }
}