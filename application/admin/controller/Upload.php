<?php

namespace app\admin\controller;
use think\Controller;
use think\File;
use think\Request;

class Upload extends Base
{
	//图片上传
    public function upload(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/images');
       if($info){
           $str= str_replace("\\",'/',$info->getSaveName());
            echo $str;
        }else{
            echo $file->getError();
        }
    }

    //会员头像上传
    public function uploadface(){
        $file = request()->file('file');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
        if($info){
            $str= str_replace("\\",'/',$info->getSaveName());
            echo $str;
        }else{
            echo $file->getError();
        }
    }
    //会员头像上传
    public function upload_file(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
       if($info){
           $str= str_replace("\\",'/',$info->getSaveName());
           echo $str;
        }else{
            echo $file->getError();
        }
    }

}