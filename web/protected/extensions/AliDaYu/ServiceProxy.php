<?php
require_once(dirname(__FILE__).'/'.'TopSdk.php');

//import class
Yii::import('application.extensions.AliDaYu.*');
Yii::import('application.extensions.AliDaYu.top.*');
Yii::import('application.extensions.AliDaYu.top.request.*');
Yii::import('application.extensions.AliDaYu.top.domain.*');
/**
 * 阿里大鱼短信服务
 */
class ServiceProxy extends CComponent{

	/**
	 * 阿里大鱼应用appkey
	 */
	public $appkey;

	/**
	 * 阿里大鱼应用secretKey
	 */
	public $secretKey;

	/**
	 * 短信发送客户端
	 */
	public $topClient;

	/**
	 * 短信发送请求体
	 */
	public $smsRequest;

	/**
	 * 短信发送请求体
	 */
	public $requestSms;

	/**
	 * 短信类别
	 */
	public $smsType = 'normal';

	/**
	 * 短信模板id
	 */
	public $smsTemplateCode = '';

	/**
	 * 接收的手机号码
	 */
	public $recNum = '';

	/**
	 * 消息内容
	 */
	public $smsParam = '';

	/**
	 * 消息签名
	 */
	public $smsFreeSignName = '';

	/**
	 *
	 */
	public $extend = '';

	/**
	 * 初始化
	 */
	public function init()
	{
		$this->topClient = new TopClient;
		//请填写自己的app key
		$this->topClient->appkey = $this->appkey;
		//请填写自己的app secret
		$this->topClient->secretKey = $this->secretKey;
		//返回数据格式
		$this->topClient->format = 'json';

		$this->requestSms = new AlibabaAliqinFcSmsNumSendRequest;
	}

	/**
	 *
	 */
	public function sendSMS($phone,$content,$sign,$template = 'SMS_11060201',$type = 'normal',$extend = '')
	{
		try{
			if($this->isValidMsg()){
				$this->requestSms->setExtend($extend);
				$this->requestSms->setSmsType($type);
				$this->requestSms->setSmsFreeSignName($sign);
				$this->requestSms->setSmsParam($content);
				//请填写需要接收的手机号码
				$this->requestSms->setRecNum($phone);
				//短信模板id
				$this->requestSms->setSmsTemplateCode($template);

				return $resp = $this->topClient->execute($this->requestSms);
			}
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	private function isValidMsg()
	{
		return true;
	}

	/**
	 * 生成6位随机数
	 */
	public function generateKey()
	{
		return rand(100000,999999);
	}

}
?>