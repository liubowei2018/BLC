<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/11
 * Time: 11:16
 */

namespace app\common\model;


use think\Model;

class ConfigCapital extends Model
{
    protected $name= 'config_capital';

    /**
     * 查询当前时间是否允许交易
     * @param $data
     * @return array|bool
     */
    public function getTransactionTime($data){
        $start_time = $this->where('name','start_time')->value('value');
        $end_time = $this->where('name','end_time')->value('value');
        $now_time = strtotime(date('H:i'));
        $start_date = strtotime(date($start_time));
        $end_date = strtotime(date($end_time));
        if($start_date < $now_time && $end_date > $now_time){
                return true;
        }else{
            return ['code'=>1012,'msg'=>'交易在'.$start_time.'-'.$end_time.'进行','data'=>$data];
        }
    }

    /**
     * 查询当前时间是否允许交易
     * @param $data
     * @return array|bool
     */
    public function getCirculationTime($data){
        $start_time = $this->where('name','circulation_start_time')->value('value');
        $end_time = $this->where('name','circulation_end_time')->value('value');
        $now_time = strtotime(date('H:i'));
        $start_date = strtotime(date($start_time));
        $end_date = strtotime(date($end_time));
        if($start_date < $now_time && $end_date > $now_time){
            return true;
        }else{
            return ['code'=>1012,'msg'=>'交易在'.$start_time.'-'.$end_time.'进行','data'=>$data];
        }
    }
}