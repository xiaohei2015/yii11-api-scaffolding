<?php

/**
 * 网页微信授权登陆
 */

class WeChatComponent extends CApplicationComponent
{
    public $appId; //AppID
    public $appSecret; // AppSecret
    public $token;//通行证
    public $authUrl = 'https://open.weixin.qq.com/connect/qrconnect?';//跳转微信登录
    public $tockenhUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?';//获取Tocken
    public $userinfoUrl = 'https://api.weixin.qq.com/sns/userinfo?';


    public function __construct() {

        //审核通过的移动应用所给的AppID和AppSecret
        $this->appId = 'wxff5922ef4f081d0b';
        $this->appSecret = 'f3521b13fb994e138fd51965409c0c3a';
        $this->token = '00000000';
    }

        /**
         * 获取微信授权url
         * @param string 授权后跳转的URL
         * @param bool 是否只获取openid，true时，不会弹出授权页面，但只能获取用户的openid，而false时，弹出授权页面，可以通过openid获取用户信息
         *   
        */
       public function getOAuthUrl($redirectUrl, $openIdOnly='snsapi_login', $state = '') {
        $redirectUrl = urlencode($redirectUrl);
        $state = uniqid();
        Yii::app()->cache->set($state, true,60*60);
        $scope = $openIdOnly ? 'snsapi_login' : 'snsapi_login';
        $oAuthUrl ="{$this->authUrl}appid={$this->appId}&redirect_uri={$redirectUrl}&response_type=code&scope=$scope&state=$state#wechat_redirect";
        return $oAuthUrl;
       }
       
       
       /**
        * 获取tocken
        * @param type $code
        * @param type $grant_type
        */
       public function getWeChatUserInfo($code,$openIdOnly='snsapi_login',$grant_type='authorization_code'){
           
           $scope = $openIdOnly ? 'snsapi_login' : 'snsapi_login';
           
           $grant_type = $grant_type?$grant_type:'authorization_code';
           
           $tockenUrl ="{$this->tockenhUrl}appid={$this->appId}&secret={$this->appSecret}&code=$code&scope=$scope&grant_type=authorization_code";
           
           $tockenData = json_decode($this->curlGet($tockenUrl));
           
           if(isset($tockenData->errcode)&&$tockenData->errcode){
               return false;
           }
           
           $userInfo = json_decode($this->getUserInfo($tockenData->access_token, $tockenData->openid));
           
           return $userInfo;
           
       }
       
       /**
        * 获取微信用户信息
        * @param type $access_token
        * @param type $openid
        * @return type
        */
       public function getUserInfo($access_token,$openid){
           $userindoUrl = $this->userinfoUrl."access_token=$access_token&openid=$openid";
           $userData = $this->curlGet($userindoUrl);
           return  $userData;
       }



       /**
        * 异步请求
        * @param type $url
        * @param type $timeout
        * @return boolean
        */
       public function curlGet($url, $timeout = 90) {
       $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url);  
        curl_setopt($ch, CURLOPT_HEADER, 0);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  
        $res = curl_exec($ch);  
        if ($err = curl_errno($ch)) { // 出错则显示错误信息
            curl_close($ch);
            return false;
        }
        curl_close($ch); // 关闭curl链接
        return $res;
    }

    /**
     * 是否绑定
     * @param $userid
     * @return int
     */
    public static function isBind($userid){
        $result = 0;
        if($userid){
            if(UserAuths::model()->findByAttributes(array('user_id'=>$userid,'app_type'=>1))){
                $result = 1;
            }
        }
        return $result;
    }

}

?>