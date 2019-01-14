<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14
 * Time: 10:29
 */

namespace app\admin\controller;


use think\Controller;
use think\Db;

class Teamupgrade extends Controller
{
    /**
     * 查询等级大于等于一的会员进行升级
     */
    public function upgrade_task(){
        $user_list  = Db::name('member_grade')->where(['level'=>['>',0]])->order('mid DESC')->select();
        foreach ($user_list as $k=>$v) {
            $res = $this->save_my_level($v);
        }
    }

    /**
     * 修改用户自身等级
     * @param data  用户个人信息
     */
    private function save_my_level($data){
        ## 如果满足当前升级条件
        switch ($data['level']){
            case 1:

                break;
            case 2:
                break;
            case 3:
                break;
            case 4:
                break;
            case 5:
                break;
            case 6:
                break;
            case 7:
                break;
        }
    }

    /**
     * 验证当前会员是否满足升级的条件
     * @param $user_team_str
     * @param $level
     */
    private function getSatisfyUpgrade($user_team_str,$level){
        $is_level = 0;
        $user_id_list = explode(',',$user_team_str);
        $config_grade = Db::name('config_grade')->where('level',$level)->find();
        foreach ($user_id_list as $k=>$v){
            $user_team_money = Db::name('member')->field('id,team_money')->where('id',$v)->find();
            $user_grade = Db::name('member_grade')->where('mid',$v)->find();
            if($user_team_money['team_money'] >= $config_grade['money'] && $user_grade['']){

            }
        }
    }


    /**
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function query_escalation(){
        $user_list = Db::name('member')->where(['team_money'=>['>',30000]])->order('id DESC')->select();
        foreach($user_list as $k=>$v){
            $this->upgrade($v['id'],$v['uuid'],0);
        }
    }
    /**
     * 用户升级  零级升一级
     * @param $userid  父级ID
     * @param $user_uuid
     * @param $money
     */
    private function upgrade($user_id,$user_uuid,$money){
        $uset_team = Db::name('member')->where('pid',$user_id)->select();
        $ConfigUpgrade = Db::name('config_upgrade')->where('lv',1)->find();
        $user_grade = Db::name('member_grade')->where('mid',$user_id)->find();
        $satisfy = 0;//满足条件的个数
        if($uset_team && $user_grade['level'] == 0){
            foreach ($uset_team as $k=>$v){
                //查询配置信息
                if($v['team_money'] >= $ConfigUpgrade['money']){
                    $satisfy = $satisfy + 1;
                }
            }
            if($satisfy >= $ConfigUpgrade['number']){
                // 满足条件的直推大于等于 升级
                $result = Db::name('member_grade')->where('mid',$user_id)->update(['level'=>1]);
                // 同时给上级添加一个等级
                Db::query("CALL TeamUpgrade($user_id,1)");
            }
        }
    }
}