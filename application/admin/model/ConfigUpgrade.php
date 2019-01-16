<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/16
 * Time: 13:40
 */

namespace app\admin\model;


use think\Model;

class ConfigUpgrade extends Model
{
    /**
     * 获取配置列表
     */
    public function getConfigLost(){
        $config = $this->select();
        return $config;
    }

    /**
     * @param $param
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function  getEditAll($param){
        $save = [];
        foreach($param['id'] as $k => $v){
            foreach ($param as $a=>$b){
                $save[$v][$a] = $b[$v];
            }
        }
        $this->startTrans();
        try{
            $this->saveAll($save);
            $this->commit();
            return true;
        }catch(\PDOException $e){
            $this->rollback();
            return false;
        }
    }
}