<?php

namespace app\api\controller;
use app\common\model\UserAuth;
use app\common\model\MoneyPropose;
use think\Controller;
use think\Db;
use think\Cache;
use app\api\model\MemberModel;
use app\api\model\MoneyModel;

/**
 * Class User  用户信息
 * @package app\api\controller
 */
class User extends Base
{
    /**
     * 用户详细信息
     */
    public function user_detail(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        //查询会员资金
        $money = new MoneyModel();
        $user_money = $money->getUserMoney($data['uuid'],'regular,current,abc_coin,today_team_money,release_regular,increment');
	    $MoneyPropose = new MoneyPropose();
        $state2 = $MoneyPropose->getMemberCount($data['uuid']);
        $user_money['frozen'] =$state2['state_0'];
        // 今日释放值  1% 提速  分红   0.5 今日团队
        $ConfigArray = ConfigCapital();
        $today_tisu = $user_money['current'] * $ConfigArray['static_bonus']/100;
        $today_team = $user_money['today_team_money'] * $ConfigArray['team_bonus']/100;
        $user_money['release_today'] = "".$today_tisu+$today_team+$user_money['release_regular']."";
        unset($user_money['today_team_money']);
        unset($user_money['release_regular']);
        $member = new MemberModel();
        $user_detail = $member->getUserDetail($data['uuid'],'uuid,nickname,is_proving,pid,account,abc_money_path')->toArray();
        $user_detail['nickname'] = $user_detail['nickname']?$user_detail['nickname']:'';
        $user_detail['pid'] = QueryParent($user_detail['pid']);
        if($user_detail['is_proving'] != 1 ){
            $UserAuth = new UserAuth();
            $user_detail['is_proving'] = $UserAuth->getApplyState($data['uuid']);
        }
        return json(['code'=>1011,'msg'=>'成功','money'=>$user_money,'user_detail'=>$user_detail]);
    }
    /**
     * 忘记支付密码
     */
    public function forget_pay(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.forget_pwd');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        //验证短信
        if($data['verify_code'] != Cache::pull('pwd_'.$data['account'])) return json(['code'=>1007,'msg'=>config('code.1007')]);
        //查询用户
        $member = new MemberModel();
        $user = $member->getUserDetail($data['uuid']);
        if($user['account'] != $data['account']) return json(['code'=>1012,'msg'=>'手机号与注册手机号不符','data'=>'']);
        $key = config('auth_key');
        $res = $member->ChangePassword($user['uuid'],2,md5($data['new_pwd'].$key));
        return json($res);
    }

    /**
     * 修改密码
     */
    public function modify_pwd(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.modify_pwd');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $member = new MemberModel();
        $key = config('auth_key');
        $user = $member->getUserDetail($data['uuid'])->toArray();
        switch ($data['type']){
            case 1://修改登陆面
                if($user['password'] != md5($data['old_pwd'].$key)) {return json(['code'=>1012,'msg'=>'原登录密码错误','data'=>'']);}
                $res = $member->ChangePassword($user['uuid'],1,md5($data['new_pwd'].$key));
                return json($res);
                break;
            case 2://修改支付密码
                if($user['pay_password'] != md5($data['old_pwd'].$key)) {return json(['code'=>1012,'msg'=>'原支付密码错误','data'=>'']);}
                $res = $member->ChangePassword($user['uuid'],2,md5($data['new_pwd'].$key));
                return json($res);
                break;
        }
    }

    public function abc_path(){
        $data = input('post.');
   
        $result = $this->validate($data,'SafetyValidate.abc_path');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $res = Db::name('member')->where('uuid',$data['uuid'])->update(['abc_money_path'=>$data['path']]);
        if($res){
            return json(['code'=>1011,'msg'=>'修改钱包地址成功','data'=>'']);
        }else{
            return json(['code'=>1012,'msg'=>'修改钱包地址失败','data'=>'']);
        }
    }

    /**
     * 钱包地址/账号验证
     */
    public function verify_identity(){
        $data = input('post.');

        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        switch($data['type']){
            case 1:
                $uuid = Db::name('member_other')->where('wallet',$data['account'])->value('uuid');
                $user_detail = Db::name('member')->field('uuid,account')->where('uuid',$uuid)->find();
                break;
            case 2:
                $user_detail =  Db::name('member')->field('uuid,account')->where('account',$data['account'])->find();
                break;
        }
        if($user_detail){
            return json(['code'=>1011,'msg'=>'成功','data'=>$user_detail]);
        }else{
            return json(['code'=>1012,'msg'=>'账号信息不存在','data'=>'']);
        }
    }

    /**
     * 添加认证申请
     */
    public function real_name(){
        $data = input('post.');
        $result = $this->validate($data,'UserValidate.real_name');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $MemberModel = new MemberModel();
        $user_detail = $MemberModel->getUserDetail($data['uuid']);
        $UserAuth = new UserAuth();
        $idcard_count = Db::name('member')->where('idcard',$data['idcard'])->count();
        if($idcard_count > 0){
            return json(['code'=>1012,'msg'=>'该身份证号已认证','data'=>'']);
        }
        if($user_detail['is_proving'] == 1) {
            return json(['code'=>1012,'msg'=>'账号已实名认证','data'=>'']);
        }
            $is_apply = $UserAuth->getApplyState($data['uuid']);
			
            if($is_apply == 2){
                return json(['code'=>1012,'msg'=>'有认证申请未审核']);
            }
        $res = $UserAuth->getAddInfo($data['uuid'],$data['name'],$data['idcard'],$data['just_card'],$data['back_card']);
        return json($res);
    }

    /**
     * 查询用户实名认证申请
     */
    public function query_user_apply(){
        $data = input('post.');
        $result = $this->validate($data,'UserValidate.common');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $UserAuth = new UserAuth();
        $url = GetDomainName().'/uploads/user/';
        $info = $UserAuth->getExamineState($data['uuid'],"name,state,idcard,CONCAT('".$url."',just_card) as just_card,CONCAT('".$url."',back_card) as back_card,info");
        $ConfigCapital = ConfigCapital();
        $info['identity_auth']=explode('#',$ConfigCapital['identity_auth']);
        return json($info);
    }
}