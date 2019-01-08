<?php

namespace app\admin\controller;
use app\admin\model\MemberModel;
use app\admin\model\MemberGroupModel;
use app\common\model\MoneyModel;
use app\common\model\MoneyPropose;
use app\common\model\MoneyRegular;
use app\common\model\UserAuth;
use think\Db;

class Member extends Base{
    //*********************************************会员组*********************************************//
    /**
     * [group 会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function group(){
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['group_name'] = ['like',"%" . $key . "%"];
        }
        $group = new MemberGroupModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');
        $count = $group->getAllCount($map);         //获取总条数
        $allpage = intval(ceil($count / $limits));  //计算总页面
        $lists = $group->getAll($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [add_group 添加会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_group(){
        if(request()->isAjax()){
            $param = input('post.');
            $group = new MemberGroupModel();
            $flag = $group->insertGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }


    /**
     * [edit_group 编辑会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_group(){
        $group = new MemberGroupModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $group->editGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign('group',$group->getOne($id));
        return $this->fetch();
    }


    /**
     * [del_group 删除会员组]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_group(){
        $id = input('param.id');
        $group = new MemberGroupModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [group_status 会员组状态]
     * @author [田建龙] [864491238@qq.com]
     */
    public function group_status(){
        $id=input('param.id');
        $status = Db::name('member_group')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member_group')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('member_group')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    }


    //*********************************************会员列表*********************************************//
    /**
     * 会员列表
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        return $this->fetch();
    }

    /**
     * 会员列表
     */
    public function member_list(){
        $key = input('key');
        $state = input('param.state');
        $type = input('param.type');
        $is_proving = input('param.is_proving');
        $activation = input('param.activation');
        $stare_time = input('param.stare_time');
        $end_time = input('param.end_time');
        $map = [];
        if($key&&$key!==""){
            $map['m.account|m.nickname'] = $key;
        }
        if($is_proving === '0' || !empty($is_proving)){
            $map['m.is_proving'] = $is_proving;
        }
        if($activation === '0' || !empty($activation)){
            $map['m.activation'] = $activation;
        }
        if($type === '0' || !empty($type)){
            $map['m.status'] = $type;
        }
        if(!empty($stare_time)){
            $stare_time = $stare_time.' 00:00:00';
            $map['m.create_time'] = ['>= time',$stare_time];
        }
        if(!empty($end_time)){
            $end_time = $end_time.' 23:59:59';
            $map['m.create_time'] = ['<= time',$end_time];
        }
        if(!empty($stare_time) && !empty($end_time)){
            $map['m.create_time'] = ['between time',[$stare_time,$end_time]];
        }
        $page = input('get.page') ? input('get.page'):1;
        $rows = input('get.rows');// 获取总条数
        $MemberLog = new MemberModel();
        $info = $MemberLog->getMemberByWhere('m.*,y.current,y.abc_coin,y.today_team_money,y.increment,y.regular',$map,$page,$rows);
        foreach ($info['lists'] as $k=>$v){
            $MoneyPropose = new MoneyPropose();
            $state2 = $MoneyPropose->getMemberCount($v['uuid']);
            $info['lists'][$k]['dongjie'] = $state2['state_0'];
            //推荐账号
            $info['lists'][$k]['parent'] = $MemberLog->getMemberParent($v['pid']);
        }
        $data['list'] = $info['lists'];
        $data['count'] = $info['count'];
        $data['page'] = $page;
        return json($data);
    }
    /**
     * 添加会员
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_member(){
        if(request()->isAjax()){
            $param = input('post.');
            $member = new MemberModel();
            if($member->getMemberCount($param['account']) === false){
                return json(['code' => -1, 'data' => '', 'msg' => '账号已注册']);
            }
            $Parent_id = $member->getParentId($param['parent_account']);
            if($Parent_id['code'] == 1011){
                $key = config('auth_key');
                $uuid = unique_num();
                $pid = $Parent_id['id'];
                $flag = $member->insertMember($uuid,$param['account'],$pid,md5(md5($param['password']).$key),md5(md5($param['pay_password']).$key));
                $only_uuid = Db::query("SELECT REPLACE(UUID(), '-', '') as only");
                $money_path = Qrcode(time(),$only_uuid[0]['only']);
                $share_url = GetDomainName().'/index/register/index/id/'.$param['account'];
                $share_path = Qrcode(time(),$share_url);
                Db::name('money')->insert(['uuid'=>$uuid]);
                Db::name('member_other')->insert(['uuid'=>$uuid,'wallet'=>$only_uuid[0]['only'],'money_path'=>$money_path,'share_path'=>'']);
                return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
            }else{
                return json(['code' => -1, 'data' => '', 'msg' => '推荐人不存在']);
            }
        }
        $group = new MemberGroupModel();
        $this->assign('group',$group->getGroup());
        return $this->fetch();
    }

    /**
     * 重置会员密码
     */
    public function reset_member_pwd(){
        $id = input('post.id');
        $MemberModel = new MemberModel();
        $login_pwd = md5(md5(123456).config('auth_key'));
        $pay_pwd = md5(md5(666666).config('auth_key'));
        $res = $MemberModel->getResetPwd($id,$login_pwd,$pay_pwd);
        return json($res);
    }

    /**
     * 编辑会员
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_member(){
        $member = new MemberModel();
        if(request()->isAjax()){
            $param = input('post.');
            $user_detail = $member->getOneMember($param['id']);
            if($user_detail['is_proving'] != 1){
                return json(['code' => 0, 'data' => '', 'msg' => '账号未实名认证，不能修改信息']);
            }
            $ole_info = ['userid'=>$user_detail['id'],'account'=>$user_detail['account'],'old_name'=>$user_detail['nickname'],'old_idcard'=>$user_detail['idcard'],'new_name'=>$param['nickname'],'new_idcard'=>$param['idcard'],'add_time'=>time()];
            $new_info = ['id'=>$param['id'],'nickname'=>$param['nickname'],'idcard'=>$param['idcard']];
            $flag = $member->editMember($new_info,$ole_info);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $group = new MemberGroupModel();
        $this->assign([
            'member' => $member->getOneMember($id),
            'group' => $group->getGroup()
        ]);
        return $this->fetch();
    }


    /**
     * 删除会员
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_member(){
        $id = input('param.id');
        $member = new MemberModel();
        $flag = $member->delMember($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * 会员状态
     * @author [田建龙] [864491238@qq.com]
     */
    public function member_status(){
        $id = input('post.id');
        $status = Db::name('member')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已冻结']);
        } else {
            $flag = Db::name('member')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已开启']);
        }

    }

    /**
     * 编辑会员信息记录
     */
    public function edit_member_log(){
        $data = input('get.');
        $account = input('get.account');
        $stare_time = input('get.stare_time');
        $end_time = input('get.end_time');
        $map = [];
        if($account&&$account!==""){
            $map['account'] = $account;
        }

        if(!empty($stare_time)){
            $stare_time = $stare_time.' 00:00:00';
            $map['create_time'] = ['>= time',$stare_time];
        }
        if(!empty($end_time)){
            $end_time = $end_time.' 23:59:59';
            $map['create_time'] = ['<= time',$end_time];
        }
        if(!empty($stare_time) && !empty($end_time)){
            $map['create_time'] = ['between time',[$stare_time,$end_time]];
        }
        $lists = Db::name('member_edit')->where($map)->order('add_time DESC')->paginate(20,false,['query'=>$data]);
        $page = $lists->render();
        $this->assign('lists',$lists);
        $this->assign('page',$page);
        $this->assign('account',$account?$account:'');
        $this->assign('stare_time',$stare_time?$stare_time:'');
        $this->assign('end_time',$end_time?$end_time:'');
        return $this->fetch();
    }

    /**
     * 用户身份证认证
     */
    public function authentication(){
            $data = input('get.');
            $key = input('param.account');
            $state = input('param.state');
            $stare_time = input('param.stare_time');
            $end_time = input('param.end_time');
            $map = [];
            if($key&&$key!==""){
                $map['m.account|u.name'] = $key;
            }
            if(!empty($state)){
                $map['u.state'] = $state;
            }
            if(!empty($stare_time)){
                $stare_time = $stare_time.' 00:00:00';
                $map['u.create_time'] = ['>= time',$stare_time];
            }
            if(!empty($end_time)){
                $end_time = $end_time.' 23:59:59';
                $map['u.create_time'] = ['<= time',$end_time];
            }
            if(!empty($stare_time) && !empty($end_time)){
                $map['u.create_time'] = ['between time',[$stare_time,$end_time]];
            }
            $UserAuth = new UserAuth();
            $info = $UserAuth->alias('u')->field('u.*,m.account')
                ->where($map)->join('member m','m.uuid=u.uuid')->order('state ASC,add_time DESC')->paginate(15,false,['query'=>$data]);
            $page = $info->render();
            $this->assign('info',$info);
            $this->assign('page',$page);
            $this->assign('account',$key);
            $this->assign('state',$state);
            $this->assign('stare_time',$stare_time);
            $this->assign('end_time',$end_time);
            return $this->fetch();
    }

    /**
     * 确认审核
     */
    public function confirm_authentication(){
        $id = input('post.id');
        $UserAuth = new UserAuth();
        $info = $UserAuth->where('id',$id)->find();
        if($info['state'] != 1){
            return json(['code'=>1012,'msg'=>'申请信息不是未审核状态']);
        }
        Db::startTrans();
        try{
            $UserAuth->where('id',$id)->update(['state'=>2]);
            Db::name('member')->where('uuid',$info['uuid'])->update(['nickname'=>$info['name'],'idcard'=>$info['idcard'],'is_proving'=>1]);
            Db::commit();
            //查询是否赠送用户定期
            $ConfigCapital = ConfigCapital();
            if($ConfigCapital['regular_gifts_state'] == 1){
                $MoneyModel = new MoneyModel();
                //增加定期
                if($ConfigCapital['regular_gifts'] > 0){
                    $user_detail= Db::name('member')->where('uuid',$info['uuid'])->find();
                    $money_controller = new \app\admin\controller\Money;
                    //直推分红
                    $money_controller->direct_distribution($user_detail['id'],$ConfigCapital['regular_gifts'] );
                    //团队业绩
					Db::query('CALL TeamPerformance('.$user_detail['id'].','.$ConfigCapital['regular_gifts'].')');
                    $MoneyModel->getModifyMoney($info['uuid'],1,1,$ConfigCapital['regular_gifts'],'实名认证赠送定期','',4,$this->admin_id,$this->admin_name);
                }
                //增加增值
                if($ConfigCapital['zengzhi_gifts'] > 0){
                    $MoneyModel->getModifyMoney($info['uuid'],5,1,$ConfigCapital['zengzhi_gifts'],'实名认证赠送增值','',4,$this->admin_id,$this->admin_name);
                }
            }
            return json(['code'=>1011,'msg'=>'确认审核成功','data'=>'']);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>'']);
        }
    }
    /**
     * 驳回申请
     */
    public function reject_authentication(){
        $id = input('post.id');
        $detail = input('post.info');
        $UserAuth = new UserAuth();
        $info = $UserAuth->where('id',$id)->find();
        if($info['state'] != 1){
            return json(['code'=>1012,'msg'=>'申请信息不是未审核状态']);
        }
        Db::startTrans();
        try{
            $UserAuth->where('id',$id)->update(['state'=>3,'info'=>$detail]);
            Db::commit();
            return json(['code'=>1011,'msg'=>'驳回审核成功','data'=>'']);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>'']);
        }
    }

    /**
     * 用户推荐人列表
     */
    public function relation_diagram(){
        $account = input('get.account');
        if($account){
            $where = [
                'u.account' => $account
            ];
            $id = Db::name('member')->alias('u')->field('u.*,m.today_team_money')->where($where)->join('money m','m.uuid = u.uuid')->find();
            if($id){
                $pid = $id['id'];
                $user_count = Db::name('member')->where(['pid'=>$pid])->count();

                if($id['activation'] ==1){
                    $str_activation = '__已激活账号__';
                }else{
                     $str_activation = '__未激活账号__';
                }
                if($id['team_state'] ==1){
                    $str_team_state = '__已激活团队__';
                }else{
                     $str_team_state = '__未激活团队__';
                }
                $user_team = Db::name('member')->where(['pid'=>$pid,'is_proving'=>1,'activation'=>1])->count();
                $str = "姓名：".$id['nickname']."，手机号：".$id['account']."，直接推荐人：".$user_count."有效直推：$user_team"."，团队业绩：".$id['today_team_money']."，团队人数：".$id['team_number'].",$str_activation$str_team_state";
                $this->assign('str',$str);
                $id = $id['id'];
                $state = 'ok';
            }else{
                $this->assign('str','');
                $state = 'error';
            }
        }else{
            $id = '0';
            $state = 'ok';
        }
        $this->assign('account',$account);
        $this->assign('state',$state);
        $this->assign('id',$id);
        return $this->fetch();
    }
    public function relation_diagram_list(){
        $id = input('param.id')?input('param.id'):input('param.pid');

        $user = Db::name('member')->alias('u')->field('u.id,u.nickname,u.account,m.today_team_money,u.team_number,u.activation,u.team_state')
            ->where(['u.pid'=>$id])->join('money m','m.uuid = u.uuid')->select();
           
        foreach ($user as $k=>$v){
            $pid = $v['id'];

                if($v['activation'] ==1){
                    $str_activation = '__已激活账号__';
                }else{
                     $str_activation = '__未激活账号__';
                }
                if($v['team_state'] ==1){
                    $str_team_state = '__已激活团队__';
                }else{
                     $str_team_state = '__未激活团队__';
                }

            $user_count = Db::name('member')->where(['pid'=>$pid])->count();
            $user_team = Db::name('member')->where(['pid'=>$pid,'is_proving'=>1,'activation'=>1])->count();
            $str = "昵称：".$v['nickname']."，手机号：".$v['account']."，直接推荐人：".$user_count."，有效直推：$user_team"."，团队业绩：".$v['today_team_money']."，团队人数：".$v['team_number'].",$str_activation$str_team_state";
            $user[$k]['name'] = $str?htmlspecialchars($str):'';
            if($user_count > 0){
                $user[$k]['isParent'] = 'true';
            }else{
                $user[$k]['isParent'] = 'false';
            }
        }

        return json($user);
    }
}
