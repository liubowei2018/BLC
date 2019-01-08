<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 9:25
 */

namespace app\common\model;


use think\Model;

class MoneyLogModel extends Model
{
    protected $name = 'money_log';
    /**
     * 查询流水记录
     * @param $field
     * @param $map
     * @param $page
     * @param $row
     * @param $order
     */
    public function getQueryLog($field,$map,$page,$row,$order){
        $count = $this->alias('r')->where($map)->join('member m','m.uuid=r.uuid')->count();
        $lists = $this->alias('r')->field($field)->where($map)->join('member m','m.uuid=r.uuid')->page($page,$row)->order($order)->select();
        foreach ($lists as $k=>$v){
            if($v['state'] == 1){
                $lists[$k]['current'] = $v['original']+$v['money'];
            }else{
                $lists[$k]['current'] = $v['original']-$v['money'];
            }
        }
        return ['count'=>$count,'lists'=>$lists];
    }
}