<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/27
 * Time: 9:07
 */

namespace app\api\controller;


use think\Cache;
use think\Controller;

class Debugging extends Controller
{
    /**
     * 模拟用户登陆
     */
    public function test_login(){
        $data = [
            'account'=>'13592669756',
            'login_pwd'=>md5(123456)
        ];
        $data['Sign']=getSign($data);
        $web_url = GetDomainName();
        $connect = '/api/login/login';
        $res = curl_post_https($web_url.$connect,$data);
        $res = json_decode($res,true);
        dump($res);
        Cache::set('test_uuid',$res['data']['uuid']);
        Cache::set('test_token',$res['data']['token']);
    }

    /**
     * 测试数据提交
     * @return mixed
     */
    public function test_api(){
        $web_url = GetDomainName();
        $connect = '/api/usermoney/increase_regular';
        $data =  array (
            'uuid' => '11199231',
            'token' => '2c5e0f8efb168e7d4530a7bafbeb75c7',
            'pay_pwd' => 'e10adc3949ba59abbe56e057f20f883e',
            'TimeStamp' => '1546322159904',
            'number' => '1001',
            'Sign' => 'a9d53848a4e06ef851d3312e69dc9684',
        );
        $data['Sign'] = getSign($data);
        $res = curl_post_https($web_url.$connect,$data);
        echo $res;
    }
}