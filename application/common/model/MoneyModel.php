<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28
 * Time: 10:05
 */

namespace app\common\model;


use think\Model;
use think\Db;
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
     * @param $admin_id
     * @param $admin_name
     */
    public function getModifyMoney($uuid,$type,$state,$number,$info='',$source_uuid='',$classify='',$admin_id,$admin_name){
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
                    }
                    break;
                case 2://活期
                    $original = $user_money['current'];
                    if($state==1){//增加
                        $str = '增加提速值成功';
                        $this->where('uuid',$uuid)->setInc('current',$number);
                    }elseif($state==2){//减少
                        $str = '减少提速值成功';
                        $this->where('uuid',$uuid)->setDec('current',$number);
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
                'is_admin'=>$admin_id,
                'admin_name'=>$admin_name
            ];
            Db::name('money_log')->insert($money_log);
            $this->commit();
            return ['code'=>1011,'msg'=>$str,'data'=>''];
        }catch(\PDOException $e){
            $this->rollback();
            return ['code'=>1012,'msg'=>$e->getMessage(),'data'=>''];
        }

    }
}