<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/11/27
 * Time: 0:43
 */

namespace app\api\model;


use think\Model;

/**
 * 体现管理
 * Class MoneyPropose
 * @package app\api\model
 */
class MoneyPropose extends Model
{
    protected $name='money_propose';

    /**
     * 发起提现申请
     * @param $uuid
     * @param $number
     * @param $money_path
     * @return array
     */
    public function getAddInfo($uuid,$number,$money_path){
        $this->startTrans();
        try{
            $data = [
                'order_number'=>'T'.time().rand(1000,9999),
                'uuid'=>$uuid,
                'number'=>$number,
                'state'=>'0',
                'money_path'=>$money_path,
                'add_time'=>time(),
            ];
            $this->insert($data);
            $this->commit();
            return ['code'=>1011,'msg'=>'提现申请成功','data'=>''];
        }catch(\PDOException $e){
            $this->rollback();
            return  ['code'=>1012,'msg'=>'提现申请失败','data'=>''];
        }
    }

    /**
     * 提现申请记录
     * @param $uuid
     * @param $page
     * @param $row
     * @param string $field
     * @return array
     */
    public function getAddLog($uuid,$page,$row,$field='*'){
        $lists = $this->field($field)->where('uuid',$uuid)->page($page,$row)->order('add_time DESC')->select();
        return ['code'=>1011,'msg'=>'成功','data'=>$lists];
    }
}