<?php

namespace app\admin\controller;
use app\admin\model\OrderCountModel;
use app\common\model\MemberModel;
use app\common\model\MoneyPropose;
use app\common\model\MoneyRecharge;
use app\common\model\MoneyRegular;
use think\Cache;
use think\Config;
use think\Loader;
use think\Db;

class Index extends Base{

    public function index(){
        return $this->fetch('/index');
    }
    /**
     * [indexPage 后台首页]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function indexPage(){
         //今日新增会员
        $MemberModel = new MemberModel();

            //全部
            $count['all_user'] = $MemberModel->getUserCount();
            //今日
            $count['today']    = $MemberModel->getUserCount([],'today');
            //认证
            $count['renzheng'] = $MemberModel->getUserCount(['is_proving'=>1]);
            //激活
            $count['activation'] = $MemberModel->getUserCount(['activation'=>1]);
            //占比计算
            $count['today_ratio'] = $count['today']?sprintf("%.2f",($count['today']/$count['all_user']))*100:0;
            $count['renzheng_ratio'] = $count['today']?sprintf("%.2f",($count['renzheng']/$count['all_user']))*100:0;
            $count['activation_ratio'] = $count['today']?sprintf("%.2f",($count['activation']/$count['all_user']))*100:0;


            //充值
            $MoneyRecharge = new MoneyRecharge();
            $money_log['recharge_log'] = $MoneyRecharge->getRechargeSum();
            //提现
            $MoneyPropose = new MoneyPropose();
            $money_log['propose_log']  = $MoneyPropose->getProposeSum();
            //定期记录
            $OrderCount = new OrderCountModel();
            $list = $OrderCount->getDayliat();
            $money_log['complete']=$list['complete'];
            $money_log['found']=$list['found'];
            $money_log['show_time']=$list['show_time'];
            //查询定期记录
            $MoneyRegular = new MoneyRegular();
            $money_log['state_0']=$MoneyRegular->getOrderCount(['state'=>0]);
            $money_log['state_1']=$MoneyRegular->getOrderCount(['state'=>1]);
            //定期今日总计
            $money_log['dingqi_today'] = Db::name('money_regular')->whereTime('add_time','d')->count();
            $money_log['time'] = date('Y-m-d H',time());
        $this->assign('time',$money_log['time']);
        $this->assign('count',$count);
        $this->assign('recharge_log',$money_log['recharge_log']);
        $this->assign('propose_log',$money_log['propose_log']);
        $this->assign('state_1',$money_log['state_1']);
        $this->assign('state_0',$money_log['state_0']);
        $this->assign('dingqi_today',$money_log['dingqi_today']);
        $this->assign('complete',json_encode($money_log['complete']));
        $this->assign('found',json_encode($money_log['found']));
        $this->assign('show_time',json_encode($money_log['show_time']));
        return $this->fetch('index2');
    }

    public function shifang(){
        Db::query("CALL TodaySettlement()");
        Db::query("CALL TodayRelease()");
        return json(['code'=>1011,'msg'=>'执行完毕','data'=>'']);
    }

    /**
     * [userEdit 修改密码]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function editpwd(){
        if(request()->isAjax()){
            $param = input('post.');
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(md5(md5($param['old_password']) . config('auth_key'))!=$user['password']){
               return json(['code' => -1, 'url' => '', 'msg' => '旧密码错误']);
            }else{
                $pwd['password']=md5(md5($param['password']) . config('auth_key'));
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//清除缓存中网站配置信息
                return json(['code' => 1, 'url' => 'index/index', 'msg' => '密码修改成功']);
            }
        }
        return $this->fetch();
    }


    /**
     * 清除缓存
     */
    public function clear() {
        if (delete_dir_file(CACHE_PATH) && delete_dir_file(TEMP_PATH)) {
            return json(['code' => 1, 'msg' => '清除缓存成功']);
        } else {
            return json(['code' => 0, 'msg' => '清除缓存失败']);
        }
    }

}
