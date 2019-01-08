<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 16:49
 */

namespace app\admin\controller;
use app\admin\model\ConfigCapital;
use think\Cache;
use think\Db;

class Capital extends Base
{
    public function config(){
        $config = new ConfigCapital();
        $list = $config->getAllConfig();
        $this->assign('config',$list);
        return $this->fetch();
    }

    /**
     * 充值 金币网配置
     */
    public function recharge_jinbi(){
        $config = new ConfigCapital();
        $list = $config->getAllConfig();
        $this->assign('config',$list);
        return $this->fetch();
    }
    /**
     * 充值 乾坤网
     */
    public function recharge_qiankun(){
        $config = new ConfigCapital();
        $list = $config->getAllConfig();
        $this->assign('config',$list);
        return $this->fetch();
    }
    /**
     * 资金配置
     */
    public function fund(){
        $config = new ConfigCapital();
        $list = $config->getAllConfig();
        $this->assign('config',$list);
        return $this->fetch();
    }
    /**
     * 加速配置
     */
    public function accelerate(){
        $config = new ConfigCapital();
        $list = $config->getAllConfig();
        $this->assign('config',$list);
        return $this->fetch();
    }

    /**
     * app配置
     * @return mixed
     */
    public function app_config(){
        $config = new ConfigCapital();
        $list = $config->getAllConfig();
        $this->assign('config',$list);
        return $this->fetch();
    }

    /**
     * 批量保存配置
     * @author
     */
    public function save($config){
        $configModel = new ConfigCapital();
        if($config && is_array($config)){
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                $configModel->SaveConfig($map,$value);
            }
        }
        Cache::rm('ConfigCapital');
        $this->success('保存成功！');
    }
}