<?php

class PlatformController extends CmainController
{
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	public function accessRules()
	{
		return array(
			array('allow', // 常规
				'actions' => array("index","login","error","city"),
				'users' => array('*'),
			),
			array('allow', // 常规
				'actions' => array("summon"),
				'users' => array('@'),
			),
			array('deny', // deny all users
				'users' => array('*'),
			),
		);
	}


	public function actionIndex()
	{
        $weibo = Yii::app()->weibo;
        $baseUrl = $weibo->getCodeUrl('api.scaffolding.com');
//        $weixin = Yii::app()->weixin;
//        $baseUrl =$weixin->getOAuthUrl('http://api.scaffolding.com/api/wechat/callback');
 	return $this->redirect($baseUrl);
 
		// $this->redirect("http://api.scaffolding.com/index.html");
		//$this->redirect("http://api.scaffolding.com/index.html");
	}

	public function actionLogin()
	{
		$this->redirect("http://api.scaffolding.com/uc_login.html");
	}



	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest){
				$this->outputJson($this->returnMsg($error['message']?:'failed'));
			}else{
				if($error["code"]==404){
					$this->redirect("/404.html?msg=".$error["message"]);
				}else{
					echo $error['message'];
				}
			}

		}
	}


	/**
	 * 城市列表
	 */
	public function actionCity()
	{
		$id = trim(Yii::app()->request->getParam("id"));
		$catid = trim(Yii::app()->request->getParam("catid"));
		$parentid = trim(Yii::app()->request->getParam("parentid"));

		if ($id != "" && !is_numeric($id)) {
			$this->outputJson($this->returnMsg('errorParams',"id"));
		}
		if ($catid != "" && !is_numeric($catid)) {
			$this->outputJson($this->returnMsg('errorParams',"catid"));
		}
		if ($parentid != "" && !is_numeric($parentid)) {
			$this->outputJson($this->returnMsg('errorParams',"parentid"));
		}
		$catid =$catid?$catid:3;

		$data_arr = null;

		//热门城市
		$criteria = new CDbCriteria();
		$criteria->condition = "DISABLED = 0 AND ISHOT = 1 AND CATID = 3";
		$criteria->order = "PRIORITY DESC";
		$model = SysRegionInfo::model()->findAll($criteria);
		foreach($model as $v){
			$data_arr["hotcity"][] = array(
				"id" => $v->ID,
				"name" => $v->NAME
			);
		}

		//省份/城市列表
		$criteria = new CDbCriteria();
		$criteria->condition = "DISABLED = 0";
		$criteria->compare("ID",$id);
		$criteria->compare("CATID",$catid);
		$criteria->compare("PARENTID",$parentid);
		$criteria->order = "PRIORITY DESC";

		$model = SysRegionInfo::model()->findAll($criteria);
		foreach($model as $v){
			$data_arr["list"][] = array(
				"id" => $v->ID,
				"name" => $v->NAME
			);
		}

		if($data_arr != null){
			$this->outputJson($this->returnMsg('success',$data_arr));
		}else{
			$this->outputJson($this->returnMsg('nodata'));
		}

	}

}