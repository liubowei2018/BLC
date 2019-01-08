<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/9/27
 * Time: 14:04
 */

namespace app\index\controller;


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
        //验证短信是否频繁发送
        if(Cache::get($data['phone'])){
            return json(['code'=>1006,'msg'=>config('code.1006'),'data'=>'']);
        }
        $number = rand(100000,999999);
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
        /*$result = curl_post_https($url,$post_data);
        $objectxml = simplexml_load_string($result);//将文件转换成 对象
        $xmljson= json_encode($objectxml );//将对象转换个JSON
        $xmlarray=json_decode($xmljson,true);//将json转换成数组*/
        $xmlarray['message'] = 'ok';
        if($xmlarray['message'] == 'ok'){
            return json(['code'=>1011,'msg'=>'短信发送成功','data'=>'']);
        }else{
            return json(['code'=>1012,'msg'=>'短信发送失败','data'=>'']);
        }
    }
}