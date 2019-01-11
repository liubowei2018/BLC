<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 15:48
 */

namespace app\api\model;


use think\Db;
use think\Model;

class MoneyModel extends Model
{
    protected $name = 'money';

    /**
     * 查询用户资金
     * @param $uuid
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     */
    public function getUserMoney($uuid,$field='*'){
        $info = $this->field($field)->where('uuid',$uuid)->find();
        return $info;
    }

    /**
     * 编辑用户资金
     * @param $uuid 用户编号
     * @param $type 操作类型
     * @param $state 增加减少
     * @param $number 操作数量
     * @param $info 详情
     * @param $source_uuid 来源uuid
     * @param $classify  1 用户转账 2 户转 3 转定期 4 充值 5 提现
     */
    public function getModifyMoney($uuid,$type,$state,$number,$info='',$source_uuid='',$classify=''){
        $user_money = $this->where('uuid',$uuid)->find();
        $this->startTrans();
        try{
            switch($type){
                case 1://定期
                    $original = $user_money['regular'];
                    if($state==1){//增加
                        $str = '增加定期成功';
                        $this->where('uuid',$uuid)->setInc('regular',$number);
                    }elseif($state==2){//减少
                        $str = '减少定期成功';
                        $this->where('uuid',$uuid)->setDec('regular',$number);
                        $money_number = $this->where('uuid',$uuid)->value('regular');
                        if($money_number < 0){
                            $this->rollback();
                            return ['code'=>1012,'mag'=>'定期余额不足','data'=>''];
                        }
                    }
                    break;
                case 2://活期
                    $original = $user_money['current'];
                    if($state==1){//增加
                        $str = '增加活期成功';
                        $this->where('uuid',$uuid)->setInc('current',$number);
                    }elseif($state==2){//减少
                        $str = '减少活期成功';
                        $this->where('uuid',$uuid)->setDec('current',$number);
                        $money_number = $this->where('uuid',$uuid)->value('current');
                        if($money_number < 0){
                            $this->rollback();
                            return ['code'=>1012,'mag'=>'活期余额不足','data'=>''];
                        }
                    }
                    break;
                case 3://可用
                    $original = $user_money['abc_coin'];
                    if($state==1){//增加
                        $str = '增加可用成功';
                        $this->where('uuid',$uuid)->setInc('abc_coin',$number);
                    }elseif($state==2){//减少
                        $str = '减少可用成功';
                        $this->where('uuid',$uuid)->setDec('abc_coin',$number);
                        $money_number = $this->where('uuid',$uuid)->value('abc_coin');
                        if($money_number < 0){
                            $this->rollback();
                            return ['code'=>1012,'mag'=>'可用余额不足','data'=>''];
                        }
                    }
                    break;
				case 4://今日释放提速
                    $original = $user_money['release_regular'];
                    if($state==1){//增加
                        $str = '增加今日释放成功';
                        $this->where('uuid',$uuid)->setInc('release_regular',$number);
                    }elseif($state==2){//减少
                        $str = '减少今日释放成功';
                        $this->where('uuid',$uuid)->setDec('release_regular',$number);
                    }
                    break;
                case 5://增值
                    $original = $user_money['increment'];
                    if($state==1){//增加
                        $str = '增加增值成功';
                        $this->where('uuid',$uuid)->setInc('increment',$number);
                    }elseif($state==2){//减少
                        $str = '减少增值成功';
                        $this->where('uuid',$uuid)->setDec('increment',$number);
                        $money_number = $this->where('uuid',$uuid)->value('increment');
                        if($money_number < 0){
                            $this->rollback();
                            return ['code'=>1012,'mag'=>'增值余额不足','data'=>''];
                        }
                    }
                    break;
                case 6://直接冻结  frozen_push
                    $original = $user_money['frozen_push'];
                    if($state==1){//增加
                        $str = '增加直接冻结成功';
                        $this->where('uuid',$uuid)->setInc('frozen_push',$number);
                    }elseif($state==2){//减少
                        $str = '减少直接冻结成功';
                        $this->where('uuid',$uuid)->setDec('frozen_push',$number);
                        $money_number = $this->where('uuid',$uuid)->value('frozen_push');
                        if($money_number < 0){
                            $this->rollback();
                            return ['code'=>1012,'mag'=>'直接冻结不足','data'=>''];
                        }
                    }
                    break;
                case 7://间接冻结   frozen_indirect
                    $original = $user_money['frozen_indirect'];
                    if($state==1){//增加
                        $str = '增加间接冻结成功';
                        $this->where('uuid',$uuid)->setInc('frozen_indirect',$number);
                    }elseif($state==2){//减少
                        $str = '减少间接冻结成功';
                        $this->where('uuid',$uuid)->setDec('frozen_indirect',$number);
                        $money_number = $this->where('uuid',$uuid)->value('frozen_indirect');
                        if($money_number < 0){
                            $this->rollback();
                            return ['code'=>1012,'mag'=>'间接冻结不足','data'=>''];
                        }
                    }
                    break;
            }
            $money_log = [
                'uuid'=>$uuid,
                'money'=>$number,
                'original'=>$original,
                'type'=>$type,
                'state'=>$state,
                'info'=>$info,
                'add_time'=>time(),
                'source_uuid'=>$source_uuid,
                'classify'=>$classify,
            ];
            Db::name('money_log')->insert($money_log);
            $this->commit();
            return ['code'=>1011,'mag'=>$str,'data'=>''];
        }catch(\PDOException $e){
            $this->rollback();
            return ['code'=>1012,'mag'=>$e->getMessage(),'data'=>''];
        }

    }

}