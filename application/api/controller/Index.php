<?php
namespace app\api\controller;

use app\admin\model\ConfigCapital;
use app\api\model\MemberModel;
use app\api\model\MoneyModel;
use app\api\model\MoneyRegular;
use think\Cache;
use app\api\model\BannerModel;
use app\api\model\ArticleModel;
use think\Db;

class Index extends Base
{
    public function index()
    {
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        //轮播图
        $banner = new BannerModel();
        $banner_lists = $banner->RotationChart();
        //中部图片
        $banner_medio = $banner->getAppMedioImg();
        //三条公告
        $article = new ArticleModel();
        $article_list = $article->HomePage();
        //用户定期 活期值
        $MoneyModel = new MoneyModel();
        $user_money = $MoneyModel->getUserMoney($data['uuid']);
       // $MoneyRegular = new MoneyRegular();
       // $regular = $MoneyRegular->getRegularCount($data['uuid']);
        return json(['code'=>1011,'msg'=>'成功','lunbo'=>$banner_lists,'zhongbu'=>$banner_medio,'gonggao'=>$article_list,'abc_coin'=>$user_money['abc_coin'],'current'=>$user_money['current'],'regular'=>$user_money['regular'],'increment'=>$user_money['increment']]);
    }
    /**
     * 新闻列表
     */
    public function news_list(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        //if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $article = new ArticleModel();
        $news_list = $article->getNewsLists(4,$data['page'],15);
        return json(['code'=>1011,'msg'=>'获取成功','data'=>$news_list]);
    }
    /**
     * 团队列表
     */
    public function team_list(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $MemberModel = new MemberModel();
        $user_detail = $MemberModel->getUserDetail($data['uuid']);
        $team_list = $MemberModel->getTeamLists($user_detail['id'],$data['page'],15);
        $team_money = Db::name('money')->where('uuid',$data['uuid'])->value('today_team_money');
        return json(['code'=>1011,'msg'=>'成功','team_money'=>$team_money,'account'=>$user_detail['account'],'team_number'=>$user_detail['team_number'],'team_count'=>$team_list['count'],'lists'=>$team_list['lists']]);
    }

    /**
     * 用户分享和钱包图片
     */
    public function user_img(){
        $data = input('post.');
        $data['uuid'] = '11199180';
        $data['token'] = '11199180';
        $data['Sign'] = getSign($data);
        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        //if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $account = Db::name('member')->where('uuid',$data['uuid'])->value('account');
        $user_other = Db::name('member_other')->field('money_path,wallet,share_path')->where('uuid',$data['uuid'])->find();
        $user_other['money_path'] = GetDomainName().'/'.$user_other['money_path'];
        
        if($user_other['share_path'] == ''){
            $user = Db::name('member')->where('uuid',$data['uuid'])->find();
            $share_url = GetDomainName().'/index/register/index/id/'.$user['account'];
            $share_path = Qrcode(time(),$share_url);
            $user_other['share_path'] =GetDomainName().'/'. $share_path;
            Db::name('member_other')->where('uuid',$data['uuid'])->update(['share_path'=>$share_path]);
        }else{
             $user_other['share_path'] = GetDomainName().'/'.$user_other['share_path'];
        }
        $user_other['share'] = GetDomainName().'/index/register/index/id/'.$account;
        $user_other['Invitation'] = "$account";
        return json(['code'=>1011,'msg'=>'成功','data'=>$user_other]);
    }

    /**
     * 充值配置信息
     */
    public function recharge_config(){
        $data = input('post.');
        $data['uuid'] = '11199180';
        $data['token'] = '11199180';
        $data['Sign'] = getSign($data);
        $result = $this->validate($data,'IndexValidate.index');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        //if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $config = ConfigCapital();
        if($config){
            $jinbi = [
                'qrcord'=>GetDomainName().'/uploads/face/'.$config['recharge_jinbi_qrcode'],
                'money_path'=>$config['recharge_jinbi_money_path'],
                'info'=>explode('#',$config['recharge_jinbi_info'])
            ];
            $qiankun = [
                'qrcord'=>GetDomainName().'/uploads/face/'.$config['recharge_qiankun_qrcode'],
                'money_path'=>$config['recharge_qiankun_money_path'],
                'info'=>explode('#',$config['recharge_qiankun_info'])
            ];
            return json(['code'=>1011,'msg'=>'获取成功','jinbi'=>$jinbi,'qiankun'=>$qiankun]);
        }else{
            return json(['code'=>1012,'msg'=>'获取失败','jinbi'=>['qrcord'=>'','money_path'=>'','info'=>[]],'qiankun'=>['qrcord'=>'','money_path'=>'','info'=>[]]]);
        }
    }
    /**
     *提示信息列表
     */
    public function tips_list(){
        $data = input('post.');

        $result = $this->validate($data,'SafetyValidate.currency');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);//签名验证
        if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
        $ConfigCapital = ConfigCapital();
        $array['code']=1011;
        $array['msg']='成功';
        $array['poundage_propose']=$ConfigCapital['poundage_propose'];
        $array['poundage_sellout']=$ConfigCapital['poundage_sellout'];
        $array['static_bonus']=$ConfigCapital['static_bonus'];
        $array['min_sellout']=$ConfigCapital['min_sellout'];
        $array['min_propose']=$ConfigCapital['min_propose'];
        $array['static_bonus']=$ConfigCapital['static_bonus'];
        $array['tips_recharge']=explode('#',$ConfigCapital['tips_recharge']);
        $array['tips_propose']=explode('#',$ConfigCapital['tips_propose']);
        $array['tips_interturn']=explode('#',$ConfigCapital['tips_interturn']);
        $array['tips_regular']=explode('#',$ConfigCapital['tips_regular']);
        $array['tips_sellout']=explode('#',$ConfigCapital['tips_sellout']);
       return json($array);
    }


    public function edition(){
        //app版本信息
        $android_edition = Db::name('edition')->field('number,edition,info,url')->where('type','1')->order('id DESC')->find();
        $android_edition['info'] = explode(' ',$android_edition['info']);
        $android_edition['url'] = GetDomainName().'/uploads/app/'.$android_edition['url'];

        $ios_edition = Db::name('edition')->where('type','2')->order('id DESC')->find();
        $result['code']=1011;
        $result['msg']="获取信息成功";
        $result['android']= $android_edition;
        $result['ios']=['edition'=>$ios_edition['edition']];
        return json($result);
    }
}
