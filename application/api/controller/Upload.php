<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/11/26
 * Time: 23:35
 */

namespace app\api\controller;


use think\Controller;
use think\File;
use think\Request;
class Upload extends Controller
{
    //图片上传
    public function uploadface(){
        $file = request()->file('image');
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/user');
            if($info){
                $str= str_replace("\\",'/',$info->getSaveName());
                return json(['code'=>1011,'msg'=>'上传成功','data'=>$str]);
            }else{
                return json(['code'=>1012,'msg'=>'上传失败','data'=>$file->getError()]);
            }
        }else{
            return json(['code'=>1012,'msg'=>'请选择图片','data'=>'']);
        }

    }
}