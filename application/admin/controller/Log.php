<?php

namespace app\admin\controller;
use app\admin\model\LogModel;
use think\Db;
use com\IpLocation;

class Log extends Base{

    /**
     * [operate_log 操作日志]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function operate_log(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['admin_id'] =  $key;
        }
        $arr=Db::name("admin")->column("id,username"); //获取用户列表
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('log')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log')->where($map)->page($Nowpage, $limits)->order('add_time desc')->select();
        $Ip = new IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        foreach($lists as $k=>$v){
            $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $lists[$k]['ipaddr'] = $Ip->getlocation($lists[$k]['ip']);
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign("search_user",$arr);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [del_log 删除日志]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_log(){
        $id = input('param.id');
        $log = new LogModel();
        $flag = $log->delLog($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * 客户端版本管理
     * @return mixed|\think\response\Json
     */
    public function client(){
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['type'] =  $key;
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('edition')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('edition')->where($map)->order('id DESC')->page($Nowpage, $limits)->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 发布客户端版本
     */
    public function add_client(){
        if(request()->isAjax()){
            $data = input('post.');
            unset($data['file']);
            $res = Db::name('edition')->insert($data);
            if($res){
                return json(['code' => 1, 'data' => '', 'msg' => '发布版本成功']);
            }else{
                return json(['code' => 0, 'data' => '', 'msg' => '发布失败']);
            }
        }
        return $this->fetch();
    }

    /**
     * 修改客户端信息
     */
    public function edit_client(){
        if(request()->isAjax()){
            $data = input('post.');
            $id = $data['id'];
            unset($data['file']);unset($data['id']);
            if(empty($data['url'])){
                unset($data['url']);
            }
            $res = Db::name('edition')->where('Id',$id)->update($data);
            if($res){
                return json(['code' => 1, 'data' => '', 'msg' => '编辑信息成功']);
            }else{
                return json(['code' => 0, 'data' => '', 'msg' => '编辑失败']);
            }
        }
        $id = input('param.id');
        $arr  = Db::name('edition')->where('id',$id)->find();
        $this->assign('arr',$arr);
        return $this->fetch();
    }

    /**
     * 删除客户端版本信息
     */
    public function del_client(){
        $id = input('param.id');
        $res = Db::name('edition')->where('Id',$id)->delete();
        if($res){
            return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => '删除失败']);
        }
    }

}
