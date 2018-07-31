<?php
require_once(dirname(__FILE__).'/'.'TopSdk.php');

//import class
Yii::import('application.extensions.AliDaYu.*');
Yii::import('application.extensions.AliDaYu.top.*');
Yii::import('application.extensions.AliDaYu.top.request.*');
Yii::import('application.extensions.AliDaYu.top.domain.*');
/**
 * ���������ŷ���
 */
class ServiceProxy extends CComponent{

	/**
	 * �������Ӧ��appkey
	 */
	public $appkey;

	/**
	 * �������Ӧ��secretKey
	 */
	public $secretKey;

	/**
	 * ���ŷ��Ϳͻ���
	 */
	public $topClient;

	/**
	 * ���ŷ���������
	 */
	public $smsRequest;

	/**
	 * ���ŷ���������
	 */
	public $requestSms;

	/**
	 * �������
	 */
	public $smsType = 'normal';

	/**
	 * ����ģ��id
	 */
	public $smsTemplateCode = '';

	/**
	 * ���յ��ֻ�����
	 */
	public $recNum = '';

	/**
	 * ��Ϣ����
	 */
	public $smsParam = '';

	/**
	 * ��Ϣǩ��
	 */
	public $smsFreeSignName = '';

	/**
	 *
	 */
	public $extend = '';

	/**
	 * ��ʼ��
	 */
	public function init()
	{
		$this->topClient = new TopClient;
		//����д�Լ���app key
		$this->topClient->appkey = $this->appkey;
		//����д�Լ���app secret
		$this->topClient->secretKey = $this->secretKey;
		//�������ݸ�ʽ
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
				//����д��Ҫ���յ��ֻ�����
				$this->requestSms->setRecNum($phone);
				//����ģ��id
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
	 * ����6λ�����
	 */
	public function generateKey()
	{
		return rand(100000,999999);
	}

}
?>