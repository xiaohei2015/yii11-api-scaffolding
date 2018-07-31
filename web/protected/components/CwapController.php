<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class CwapController extends CmainController
{
	public $layout='//layouts/wap';

    function wx_get_token() {
        $token = Yii::app()->cache->get('access_token');
        if (!$token) {
            $res = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx582d08288eb27895&secret=f1640743073962dfed6f077930d71506');
            $res = json_decode($res, true);
            $token = $res['access_token'];
            // 注意：这里需要将获取到的token缓存起来（或写到数据库中）
            // 不能频繁的访问https://api.weixin.qq.com/cgi-bin/token，每日有次数限制
            // 通过此接口返回的token的有效期目前为2小时。令牌失效后，JS-SDK也就不能用了。
            // 因此，这里将token值缓存1小时，比2小时小。缓存失效后，再从接口获取新的token，这样
            // 就可以避免token失效。
            Yii::app()->cache->set('access_token', $token, 3600);
        }
        return $token;
    }
    function wx_get_jsapi_ticket(){
        $ticket = "";
        do{
            $ticket = Yii::app()->cache->get('wx_ticket');
            if (!empty($ticket)) {
                break;
            }
            $token = Yii::app()->cache->get('access_token');
            if (empty($token)){
                $this->wx_get_token();
            }
            $token = Yii::app()->cache->get('access_token');
            if (empty($token)) {
                break;
            }
            $url2 = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi",$token);
            $res = file_get_contents($url2);
            $res = json_decode($res, true);
            $ticket = $res['ticket'];
            // 注意：这里需要将获取到的ticket缓存起来（或写到数据库中）
            // ticket和token一样，不能频繁的访问接口来获取，在每次获取后，我们把它保存起来。
            Yii::app()->cache->set('wx_ticket', $ticket, 3600);
        }while(0);
        return $ticket;
    }



}