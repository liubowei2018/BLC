<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 10:36
 */

namespace app\api\controller;


use think\Controller;
use think\Cache;
use think\Db;
class Sms extends Controller
{
    /**
     * phone 用户手机号码
     * type 发送短信的类型 1注册 2 找回密码
     */
    public function index(){
        $data = input('post.');
        $result = $this->validate($data,'UserValidate.sms');
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        $Sign = getSign($data);
        if($Sign != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);
        $ConfigCapital = ConfigCapital();
        if($ConfigCapital['sms_state'] != 1){
             $number = 123456;
        }else{
             $number = rand(100000,999999);
        }   
        switch ($data['type']){
            case 1:
                //注册
                Cache::set('zc_'.$data['phone'],$number,300);
                $str = "【BLC】注册短信验证码为：$number ,短信有效期为5分钟";
                break;
            case 2:
                //修改信息
                $is_user = Db::name('member')->where('account',$data['phone'])->find();
                if(!$is_user){
                    return json(['code'=>1005,'msg'=>config('code.1005'),'data'=>'']);
                }else{
                    Cache::set('pwd_'.$data['phone'],$number,300);
                    $str = "【BLC】短信验证码为：$number ,短信有效期为5分钟，如非本人操作请忽略此条信息";
                }
                break;
                case 3:
                    $user_detail = Db::name('member')->where('uuid',$data['uuid'])->find();
                    if($user_detail['account'] != $data['phone']){
                        return json(['code'=>1012,'msg'=>'手机号与自身手机号不服','data'=>'']);
                    }
                    Cache::set('pwd_'.$data['phone'],$number,300);
                    $str = "【BLC】短信验证码为：$number ,短信有效期为5分钟，如非本人操作请忽略此条信息";
                    break;
        }
        $url="http://121.42.138.95:8888/sms.aspx";
        $post_data['action']="send";
        $post_data['userid']="599";
        $post_data['account']="blc";
        $post_data['password']="cz12659";
        $post_data['mobile']=$data['phone'];
        $post_data['content']=$str;
        $post_data['sendTime']="";
        $post_data['extno']="";
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        if($ConfigCapital['sms_state'] != 1){
            $xmlarray['message'] = 'ok';
        }else{
            $result = curl_post_https($url,$post_data);
            $objectxml = simplexml_load_string($result);//将文件转换成 对象
            $xmljson= json_encode($objectxml );//将对象转换个JSON
            $xmlarray=json_decode($xmljson,true);//将json转换成数组
        }
        if($xmlarray['message'] == 'ok'){
            return json(['code'=>1011,'msg'=>'短信发送成功','data'=>'']);
        }else{
            return json(['code'=>1012,'msg'=>'短信发送失败','data'=>'']);
        }
    }
}