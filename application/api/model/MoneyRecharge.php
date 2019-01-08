<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/11/27
 * Time: 0:03
 */

namespace app\api\model;


use think\Model;

/**
 * 充值管理
 * Class MoneyRecharge
 * @package app\api\model
 */
class MoneyRecharge extends Model
{
    protected $name='money_recharge';

    /**
     * 用户充值 可用 申请
     * @param $uuid
     * @param $number
     * @param $path
     * @param $trade
     * @param $type
     * @return array
     */
    public function getAddInfo($uuid,$number,$path,$trade,$type){
        $this->startTrans();
        try{
            $data = [
                'order_number'=>'C'.time().rand(1000,9999),
                'uuid'=>$uuid,
                'number'=>$number,
                'state'=>'0',
                'path'=>$path,
                'add_time'=>time(),
                'trade'=>$trade,
                'type'=>$type
            ];
             $this->insert($data);
            $this->commit();
            return ['code'=>1011,'msg'=>'充值申请成功','data'=>''];
        }catch(\PDOException $e){
            $this->rollback();
            return  ['code'=>1012,'msg'=>'充值申请失败','data'=>''];
        }
    }

    /**
     * 查询充值记录
     * @param $uuid
     * @param $page
     * @param $row
     * @param $field
     * @return array
     */
    public function getAddLog($uuid,$page,$row,$field='*'){
        $lists = $this->field($field)->where('uuid',$uuid)->page($page,$row)->order('add_time DESC')->select();
        foreach ($lists as $k=>$v){
            if($v['type'] == 1){
                $lists[$k]['detail'] = "金币网";
            }else{
                $lists[$k]['detail'] = "乾坤网";
            }
        }
        return ['code'=>1011,'msg'=>'成功','data'=>$lists];
    }
}