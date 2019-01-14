<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 16:26
 */

namespace app\api\controller;
use app\api\model\MoneyModel;
use app\common\model\ConfigCapital;
use think\Cache;
use app\api\model\MemberModel;
use app\api\model\MoneyRecharge;
use app\api\model\MoneyPropose;
use app\api\model\MoneyRegular;
use think\Db;

class Usermoney extends Base
{
    /**
     * abc币充值
     * 充值 可用
     */
    public function abc_recharge(){
        $data = input('post.');
        $result = $this->validate($data,'MoneyValidate.abc_recharge');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $trade_count = Db::name('money_recharge')->where(['trade'=>$data['trade'],'state'=>['<',2]])->count();
        if($trade_count > 0){
            return json(['code'=>1012,'msg'=>'交易id已使用，请查询是否正确','data'=>'']);
        }
        $ConfigCapital = new ConfigCapital();
        $TransactionTime = $ConfigCapital->getCirculationTime('');
        if($TransactionTime !== true) return json($TransactionTime);
        $member =new MemberModel();
        $user_pay_pwd = $member->getValidatePwd($data['uuid'],$data['pay_pwd']);
        if($user_pay_pwd === false) return json(['code'=>1012,'msg'=>'支付密码错误','data'=>'']);
        $user_status = $member->getUserState($data['uuid'],''); //验证账户是否可用
        if($user_status !== true) return json($user_status);
        $MoneyRecharge = new MoneyRecharge();
        $res = $MoneyRecharge->getAddInfo($data['uuid'],$data['number'],$data['img_path'],$data['trade'],$data['type']);
        return json($res);
    }

    /**
     * 充值\提现 可用记录
     */
    public function recharge_log(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        switch($data['type']){
            case 1:
                $MoneyRecharge = new MoneyRecharge();
                $res = $MoneyRecharge->getAddLog($data['uuid'],$data['page'],15,"number,state,FROM_UNIXTIME(add_time) as add_time,info,type");
                return json($res);
                break;
            case 2:
                $MoneyPropose = new MoneyPropose();
                $res = $MoneyPropose->getAddLog($data['uuid'],$data['page'],15,"number,state,FROM_UNIXTIME(add_time) as add_time,info,money_path as detail");
                return json($res);
                break;
        }
    }

    /**
     * 可用提出
     */
    public function propose(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.abc_tixian');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
         //正式数据
        if($data['verify_code'] != Cache::pull('pwd_'.$data['account'])) return json(['code'=>1007,'msg'=>config('code.1007')]);
        $ConfigCapital = new ConfigCapital();
        $TransactionTime = $ConfigCapital->getCirculationTime('');
        if($TransactionTime !== true) return json($TransactionTime);
        $member =new MemberModel();
        $user_status = $member->getUserState($data['uuid'],''); //验证账户是否可用
        if($user_status !== true) return json($user_status);
        $user_pay_pwd = $member->getValidatePwd($data['uuid'],$data['pay_pwd']);
        if($user_pay_pwd === false) return json(['code'=>1012,'msg'=>'支付密码错误','data'=>'']);
        $MoneyModel = new MoneyModel();
        $user_detail = $MoneyModel->getUserMoney($data['uuid']);
        if($user_detail['abc_coin'] < $data['number'])  return json(['code'=>1012,'msg'=>'可用金额不足','data'=>'']);
        $Config = ConfigCapital();
        if($data['number'] < $Config['min_propose']){
          return  json(['code'=>1012,'msg'=>'提出金额不能小于'. $Config['min_propose'],'data'=>'']);
        }
        $MoneyModel->getModifyMoney($data['uuid'],3,2,$data['number'],'可用金额提现','',5);
        $MoneyPropose = new MoneyPropose();
        $res = $MoneyPropose->getAddInfo($data['uuid'],$data['number'],$data['money_path']);
        return json($res);
    }

    /**
     * 增加定期
     */
    public function increase_regular(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $member =new MemberModel();
	    $user_status = $member->getUserState($data['uuid'],[]); //验证账户是否可用
        if($user_status !== true) return json($user_status);
        $ConfigCapital = new ConfigCapital();
        $TransactionTime = $ConfigCapital->getTransactionTime([]);
        if($TransactionTime !== true) return json($TransactionTime);
        $user_pay_pwd = $member->getValidatePwd($data['uuid'],$data['pay_pwd']);
        if($user_pay_pwd === false) return json(['code'=>1012,'msg'=>'支付密码错误','data'=>[]]);
        $MoneyModel = new MoneyModel();
        $user_detail = $MoneyModel->getUserMoney($data['uuid']);
        if($user_detail['abc_coin'] < $data['number'])  return json(['code'=>1012,'msg'=>'可用金额不足','data'=>[]]);
        $ConfigCapital = ConfigCapital();
        if($data['number'] < $ConfigCapital['turn_keyong']) return json(['code'=>1012,'msg'=>'买入不能低于'.$ConfigCapital['turn_keyong'],'data'=>[]]);
        $MoneyRegular = new MoneyRegular();//添加定期记录
        //根据比例算定期
        if($ConfigCapital['turn_regular'] > 0){
            $regular_number = $data['number']*$ConfigCapital['turn_regular']/100;
            if($regular_number+$user_detail['regular'] > $ConfigCapital['regular_max_num']){
                return json(['code'=>1012,'msg'=>'定期最多持有'.$ConfigCapital['regular_max_num'],'data'=>[]]);
            }
        }
        //根据比例算增值
        if($ConfigCapital['turn_increment'] > 0){
            $increment_number = $data['number']*$ConfigCapital['turn_increment']/100;
        }

        //扣款
        $money_resulr = $MoneyModel->getModifyMoney($data['uuid'],3,2,$data['number'],'可用买入定期/增值','',3);
        if($money_resulr['code'] != 1011) return json($money_resulr);

        Db::startTrans();
        try{
            //用户第一次买入激活账户
            $user_info = $member->getUserDetail($data['uuid']);
            if($user_info['activation'] != 1 && $regular_number >= $ConfigCapital['active_member']){
                Db::name('member')->where('uuid',$user_info['uuid'])->update(['activation'=>1]);
                Db::query('CALL TeamNumber('.$user_info['id'].')');
            }
            if($user_info['team_state'] != 1 && $regular_number >= $ConfigCapital['active_team']){
                Db::name('member')->where('uuid',$user_info['uuid'])->update(['team_state'=>1]);
            }
            //直推分红
            $user_info = $member->getUserDetail($data['uuid']);
            $this->direct_distribution($user_info['id'],$data['number']);
             //添加定期记录
            if($regular_number > 0){
                $MoneyModel->getModifyMoney($data['uuid'],1,1,$regular_number,'可用买入','',3);
            }
            if($increment_number > 0){
                // 添加增值记录
                $MoneyModel->getModifyMoney($data['uuid'],5,1,$increment_number,'可用买入','',3);
            }
            //增加团队业绩 和 今日业绩
            Db::query('CALL TeamPerformance('.$user_info['id'].','.$data['number'].')');
            //查询上级是否满足升级
            $this->upgrade($user_info['id'],$user_info['uuid'],$data['number']);
            Db::commit();
            return json(['code'=>1011,'msg'=>'买入成功','data'=>[]]);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>[]]);
        }
    }

    /**
     * 二级分红
     * @param $id
     * @param $money  买入金额
     */
    private function TwoBonus($uuid,$money){
        $MemberModel = new MemberModel();
        $user_detail = $MemberModel->getUserDetail($uuid,'id,pid,account,uuid');
         $Config = ConfigCapital();
	        //第一级
          $regular_count = Db::name('money_regular')->where('uuid',$uuid)->count();
	        $Parent = $MemberModel->getQueryIdDetail($user_detail['pid'],'id,uuid,pid,is_proving');
	        if($Parent && $Parent['is_proving'] == 1){
	        	$Parent_count = Db::name('member')->where(['pid'=>$Parent['id'],'is_proving'=>1,'activation'=>1])->count();
	        	if($Parent_count >= $Config['directpush_number'] ){
	        	$config_capital1 = Db::name('config_capital')->where('name','directpush_bonus')->value('value');
	            $ParentBonus = $money*$config_capital1/100;
	            //加入
	            $MoneyModel = new MoneyModel();
	            $ParentMoney = $MoneyModel->getModifyMoney($Parent['uuid'],4,1,$ParentBonus,'直接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
	        }
	        }
            //第二级
            if($Parent){
            	$Grandpa = $MemberModel->getQueryIdDetail($Parent['pid'],'id,uuid,pid,is_proving');
	            if($Grandpa && $Grandpa['is_proving'] == 1){
	            	$Grandpa_count = Db::name('member')->where(['pid'=>$Grandpa['id'],'is_proving'=>1,'activation'=>1])->count();
	            	if($Grandpa_count >= $Config['directpush_number'] ){
	                $config_capital2 = Db::name('config_capital')->where('name','indirect_bonus')->value('value');
	                $GrandpaBonus = $money*$config_capital2/100;
                    $MoneyModel = new MoneyModel();
	                $GrandpaMoney = $MoneyModel->getModifyMoney($Grandpa['uuid'],4,1,$GrandpaBonus,'间接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
	            	}
	            }
            }

    }
    /**
     * 一代二代分润
     * @param $id 用户id
     * @param $money 进入定期金额
     */
    public function direct_distribution($id,$money){
        $member = new MemberModel();
        $MoneyModel = new MoneyModel();
        $config = ConfigCapital();
        $user_detail = $member->getUserOne($id);
        if ($user_detail['pid'] > 0){
            //一代
            $two_user = $member->getUserOne($user_detail['pid']);
            //符合 条件 发放  未冻结  账号激活
            if($two_user && $two_user['status'] == 1 && $two_user['is_proving'] == 1 && $two_user['activation']){
                //查询直推人数
                $two_count = Db::name('member')->where(['pid'=>$two_user['id'],'is_proving'=>1,'activation'=>1])->count();
                if($two_count >= $config['directpush_number']){
                    $two_release = $money*$config['directpush_bonus']/100;
                    $MoneyModel->getModifyMoney($two_user['uuid'],4,1,$two_release,'直接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
                }else{
                    // 不符合条件的进入 直接冻结
                    $two_release = $money*$config['directpush_bonus']/100;
                    $MoneyModel->getModifyMoney($two_user['uuid'],6,1,$two_release,'（冻结）直接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
                }
            }else{
                // 不符合条件的进入 直接冻结
                $two_release = $money*$config['directpush_bonus']/100;
                $MoneyModel->getModifyMoney($two_user['uuid'],6,1,$two_release,'（冻结）直接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
            }
            //二代
            if($two_user && $two_user['pid'] > 0){
                $three_user = $member->getUserOne($two_user['pid']);
                if($three_user && $three_user['status'] == 1 && $two_user['is_proving'] == 1 && $three_user['activation']){
                    //查询直推人数
                    $three_count = Db::name('member')->where(['pid'=>$three_user['id'],'is_proving'=>1,'activation'=>1])->count();
                    if($three_count >= $config['indirect_number']){
                        $three_release = $money * $config['indirect_bonus']/100;
                        $MoneyModel->getModifyMoney($three_user['uuid'],4,1,$three_release,'间接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
                    }else{
                        $three_release = $money * $config['indirect_bonus']/100;
                        $MoneyModel->getModifyMoney($three_user['uuid'],7,1,$three_release,'（冻结）间接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
                    }
                }else{
                    $three_release = $money * $config['indirect_bonus']/100;
                    $MoneyModel->getModifyMoney($three_user['uuid'],7,1,$three_release,'（冻结）间接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6');
                }
            }
        }

    }

    public function ceshi(){
        $this->upgrade(2,2,2);
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

    /**
     * 定期买入记录
     */
    public function regular_lists(){
        $data = input('post.');

        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $MoneyRegular = new MoneyRegular();
        $lists = $MoneyRegular->getRegularLog($data['uuid'],$data['page'],15,'id,number,surplus,state,today,FROM_UNIXTIME(add_time) as add_time');
        return json($lists);
    }

    /**
     * 可用和活期互转
     */
    public function interturn(){
        $data = input('post.');

        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $member =new MemberModel();
        $user_status = $member->getUserState($data['uuid'],''); //验证账户是否可用
        if($user_status !== true) return json($user_status);
        $ConfigCapital = new ConfigCapital();
        $TransactionTime = $ConfigCapital->getTransactionTime(''); // 市场交易时间
        if($TransactionTime !== true) return json($TransactionTime);
        $user_pay_pwd = $member->getValidatePwd($data['uuid'],$data['pay_pwd']);
        if($user_pay_pwd === false) return json(['code'=>1012,'msg'=>'支付密码错误','data'=>'']);
        switch ($data['type']){
            case 1:
                $res = $this->available($data['uuid'],$data['number']);
                break;
            case 2:
                $res = $this->current($data['uuid'],$data['number']);
                break;
        }
        return json($res);
    }

    /**
     * 可用转入活期
     * @param $uuid
     * @param $number
     */
    private function available($uuid,$number){
        //活期转入最大值未定期扩大的倍数
        $ConfigCapital = ConfigCapital();
        //用户第一次买入激活账户
        $member = new MemberModel();
        $user_info = $member->getUserDetail($uuid);
        if($user_info['activation'] != 1 && $number >= $ConfigCapital['active_member']){
            Db::name('member')->where('uuid',$user_info['uuid'])->update(['activation'=>1]);
        }

        $MoneyRegular = new MoneyRegular();
        $regular_count = $MoneyRegular->getRegularCount($uuid);
        //查询用户资金
        $MoneyModel = new MoneyModel();
        $user_money = $MoneyModel->getUserMoney($uuid);
        if($user_money['current']+$number > $user_money['regular']*$ConfigCapital['conversion_ratio']){
            return ['code'=>1012,'msg'=>'提速值不能大于定期值的'.$ConfigCapital['conversion_ratio'].'倍','data'=>''];
        }
        if($user_money['abc_coin'] < $number){
            return ['code'=>1012,'msg'=>'可用金额不足请充值','data'=>''];
        }
        $is_error = true;
        //减去可用
        $keyong = $MoneyModel->getModifyMoney($uuid,3,2,$number,'可用转入提速值','',2);
        if($keyong['code'] == 1012) {$is_error = false; return $keyong;}
        $regular = $MoneyModel->getModifyMoney($uuid,2,1,($number*$ConfigCapital['turn_current']/100),'可用转入提速值','',2);
        if($regular['code'] == 1012){$is_error = false; return $keyong;}
        $MemberModel = new MemberModel();
        $ConfigCapital = ConfigCapital();
        $user_detail = $MemberModel->where('uuid',$uuid)->find();
        if($user_detail['activation'] != 1 &&  $number>= $ConfigCapital['active_member']){
            Db::name('member')->where('uuid',$user_detail['uuid'])->update(['activation'=>1]);
            Db::query('CALL TeamNumber('.$user_detail['id'].')');
        }
        if($is_error === true){
            return ['code'=>1011,'msg'=>'转入提速值成功','data'=>''];
        }
    }

    /**
     * 活期转入可用
     * @param $uuid
     * @param $number
     */
    private function current($uuid,$number){
        //查询用户资金
        $MoneyModel = new MoneyModel();
        $user_money = $MoneyModel->getUserMoney($uuid);
        if($user_money['current'] < $number){
            return ['code'=>1012,'msg'=>'提速值不足，无法转出','data'=>''];
        }
        //减去可用
        $is_error = true;
        $keyong = $MoneyModel->getModifyMoney($uuid,3,1,$number,'提速值转入可用','',2);
        if($keyong['code'] == 1012) {$is_error = false; return $keyong;}
        $regular = $MoneyModel->getModifyMoney($uuid,2,2,$number,'提速值转入可用','',2);
        if($regular['code'] == 1012){$is_error = false; return $keyong;}
        if($is_error === true){
            return ['code'=>1011,'msg'=>'转入可用成功','data'=>''];
        }
    }

    /**
     * 可用 和 提速值 记录
     */
    public function money_log(){
        $data = input('post.');

        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $where['uuid']=$data['uuid'];
        $where['type'] = $data['type'];
        $classify = input('post.classify');
        if($classify){
            $where['classify'] = $classify;
        }
        $lists = Db::name('money_log')->field('id,money,info,state,FROM_UNIXTIME(add_time) as add_time')->where($where)->page($data['page'],15)->order('add_time DESC')->select();
        return json(['code'=>1011,'msg'=>'成功','data'=>$lists]);
    }

    /**
     *用户之间转账  可用
     */
    public function member_roll_out(){
        $data = input('post.');

        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $MemeberModel = new MemberModel();
		$user_status = $MemeberModel->getUserState($data['uuid'],''); //验证账户是否可用
        if($user_status !== true) return json($user_status);
        //验证密码
        $psw_state = $MemeberModel->getValidatePwd($data['uuid'],$data['pay_pwd']);
        if($psw_state === false) return json(['code'=>1012,'msg'=>'支付密码错误','data'=>'']);
        //个人信息
        $user_member = $MemeberModel->getUserDetail($data['uuid']);
        //转入账号信息
        $out_member = $MemeberModel->getUserDetail($data['account']);
        if(!$out_member){return json(['code'=>1012,'msg'=>'转入账号不存在','data'=>'']);}
        $MoneyModel = new MoneyModel();
        $user_money = $MoneyModel->getUserMoney($data['uuid']);
        if($user_money['abc_coin'] < $data['number']) return json(['code'=>1012,'msg'=>'账户可用金额不足','data'=>'']);
        $config = ConfigCapital();
        $shouxu = $data['number']*$config['poundage_sellout']/100;
        $MoneyModel->startTrans();
        try{
            //减去转出人的钱
            $MoneyModel->getModifyMoney($data['uuid'],3,2,$data['number'],'给'.$data['account'].'转账，手续费为：'.$shouxu,$out_member['uuid'],1);
            //增加转入人的钱
            $MoneyModel->getModifyMoney($out_member['uuid'],3,1,$data['number']-$shouxu,'由'.$user_member['account'].'转账，手续费为：'.$shouxu,$data['uuid'],1);

            $MoneyModel->commit();
            return json(['code'=>1011,'msg'=>'给'.$out_member['account'].'转账成功','data'=>'']);
        }catch(\PDOException $e){
            $MoneyModel->rollback();
            return json(['code'=>1012,'msg'=>'转账失败','data'=>'']);
        }
    }


}