<?php

namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Cache;
use app\api\model\ArticleModel;
/**
 * swagger: 文章
 */
class Article extends Base
{
	/**
	 * 列表
	 */
	public function article_list(){
		$data = input('post.');
		$result = $this->validate($data,'SafetyValidate.article');//数据验证
		if($result !== true) return json(['code'=>1015,'msg'=>$result,'data'=>[]]);
		if(getSign($data) != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013'),'data'=>[]]);//签名验证
		if(Cache::get($data['uuid'])['token'] != $data['token']) return json(['code'=>1004,'msg'=>config('code.1004')]);//登陆验证
		$article = new ArticleModel();
		$lists = $article->getArticleLists($data['type'],$data['page'],15);
		return json(['code'=>1011,'msg'=>'成功','data'=>$lists]);
	}
}