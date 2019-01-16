<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class MemberModel extends Model
{
    protected $name = 'member';  
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getMemberByWhere($field,$map, $Nowpage, $limits)
    {
        $count = $this->alias('m')->where($map)->join('money y','m.uuid=y.uuid')->count();
        $lists = $this->alias('m')->field($field)->where($map)->join('money y','m.uuid=y.uuid')->page($Nowpage, $limits)->order('m.id desc')->select();
        return ['count'=>$count,'lists'=>$lists];
    }
    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getMemberExcel($field,$map, $Nowpage, $limits,$excel_state)
    {
        if($excel_state == 1){
            return  $this->alias('m')->field($field)->where($map)->join('money y','m.uuid=y.uuid')->page($Nowpage, $limits)->order('m.id desc')->select()->toArray();

        }else{
            return  $this->alias('m')->field($field)->where($map)->join('money y','m.uuid=y.uuid')->order('m.id desc')->select()->toArray();

        }
    }
    /**
     * 查询账号是否注册
     * @param $account
     */
    public function getMemberCount($account){
        $res = $this->where('account',$account)->count();
        if($res > 0){
            return false;
        }else{
            return true;
        }
    }
    /**
     * 查询父级信息
     * @param $account
     */
    public function getParentId($account){
        $id = $this->where('account',$account)->value('id');
        if($id){
            return ['code'=>1011,'id'=>$id];
        }else{
            return ['code'=>1012,'id'=>''];
        }
    }
    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    /**
     * 重置会员密码
     * @param $id
     * @param $log_pwd
     * @param $pay_pwd
     */
    public function getResetPwd($id,$log_pwd,$pay_pwd){
        $this->startTrans();
        try{
            $this->where('id',$id)->update(['password'=>$log_pwd,'pay_password'=>$pay_pwd]);
            $this->commit();
            return ['code'=>1011,'msg'=>'重置密码成功'];
        }catch (\Exception $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>'重置密码失败'.$exception->getMessage()];
        }
    }

    /**
     * 返回父级账号
     * @param $pid
     */
    public function getMemberParent($pid){
        $account = $this->where('id',$pid)->value('account');
        return $account;
    }

    /**
     * 添加会员
     * @param $uuid
     * @param $account
     * @param $pid
     * @param $login_pwd
     * @param $pay_pwd
     * @return array
     * @throws \think\exception\PDOException
     */
    public function insertMember($uuid,$account,$pid,$login_pwd,$pay_pwd)
    {
            $this->startTrans();
            try{
                $data = [
                    'uuid'=>$uuid,
                    'account'=>$account,
                    'pid'=>$pid,
                    'password'=>$login_pwd,
                    'pay_password'=>$pay_pwd,
                    'mobile'=>$account,
                    'create_time'=>time(),
                    'status'=>1,
                    'is_proving'=>0
                ];
                $this->insert($data);
                $this->commit();
                return ['code' => 1, 'data' => '', 'msg' => '注册成功'];
            }catch( \PDOException $e){
                $this->rollback();
                return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
    }

    /**
     * 编辑信息
     * @param $param
     * @param $log
     */
    public function editMember($param,$log)
    {
        $result =  $this->validate('MemberValidate');
        try{
            $result =  $this->validate('MemberValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                Db::name('member_edit')->insert($log);
                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneMember($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除管理员
     * @param $id
     */
    public function delUser($id)
    {
        try{

            $this->where('id', $id)->delete();
            Db::name('auth_group_access')->where('uid', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function delMember($id)
    {
        try{
            $map['closed']=1;
            $this->save($map, ['id' => $id]);
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}