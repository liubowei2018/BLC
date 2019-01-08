<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27
 * Time: 17:13
 */

namespace app\index\controller;


use think\Controller;
use think\Cache;
use think\Db;
use app\api\model\MemberModel;
class Register extends Controller
{
    /**
     * 注册页面
     */
    public function index(){
        $id = input('param.id');
        $this->assign('id',$id);
        return $this->fetch();
    }
    public function english(){
        $id = input('param.id');
        $this->assign('id',$id);
        return $this->fetch();
    }
    public function korean(){
        $id = input('param.id');
        $this->assign('id',$id);
        return $this->fetch();
    }
    /**
     *注册
     * 手机号 推荐人 验证码 一级密码  二级密码
     */
    public function register(){
        $data = input('post.');
        $key = config('auth_key');
        $result = $this->validate($data,'LoginValidate.reg');
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if($data['verify_code'] != Cache::pull('zc_'.$data['account'])){
            return json(['code'=>1007,'msg'=>config('code.1007'),'data'=>'']);
        }
        $member = new MemberModel();
        $member_count = $member->member_count($data['account']);
        if($member_count >0 ) return json(['code'=>1012,'msg'=>'账号已注册','data'=>'']);
        $referee_user = Db::name('member')->where('account',$data['invite'])->find();
        if(!$referee_user) return json(['code'=>1012,'msg'=>'推荐人不存在','data'=>'']);
        Db::startTrans();
        try{
            //添加用户表
            $uuid = unique_num();
            $member->RegisterUser($uuid,$data['account'],$referee_user['id'],md5(md5($data['login_pwd']).$key),md5(md5($data['pay_pwd']).$key),1);
            $only_uuid = Db::query("SELECT REPLACE(UUID(), '-', '') as only");
            $money_path = Qrcode(time(),$only_uuid[0]['only']);
            Db::name('money')->insert(['uuid'=>$uuid]);
            Db::name('member_other')->insert(['uuid'=>$uuid,'wallet'=>$only_uuid[0]['only'],'money_path'=>$money_path,'share_path'=>'']);
            Db::commit();
            return json(['code'=>1011,'msg'=>'注册成功','data'=>'']);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>'']);
        }
    }

    /**
     * 国外注册
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function foreign_register(){
        $data = input('post.');
        $key = config('auth_key');
        $result = $this->validate($data,'LoginValidate.foreign_register');
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        $member = new MemberModel();
        $member_count = $member->member_count($data['account']);
        if($member_count >0 ) return json(['code'=>1012,'msg'=>'账号已注册','data'=>'']);
        $referee_user = Db::name('member')->where('account',$data['invite'])->find();
        if(!$referee_user) return json(['code'=>1012,'msg'=>'推荐人不存在','data'=>'']);
        Db::startTrans();
        try{
            //添加用户表
            $uuid = unique_num();
            $member->RegisterUser($uuid,$data['account'],$referee_user['id'],md5(md5($data['login_pwd']).$key),md5(md5($data['pay_pwd']).$key),2);
            $only_uuid = Db::query("SELECT REPLACE(UUID(), '-', '') as only");
            $money_path = Qrcode(time(),$only_uuid[0]['only']);
            Db::name('money')->insert(['uuid'=>$uuid]);
            Db::name('member_other')->insert(['uuid'=>$uuid,'wallet'=>$only_uuid[0]['only'],'money_path'=>$money_path,'share_path'=>'']);
            Db::commit();
            return json(['code'=>1011,'msg'=>'注册成功','data'=>'']);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>'']);
        }
    }
    public function download(){

        return $this->fetch();
    }
}