<?php
include_once(__dir__.'/weibo/config.php');
include_once(__dir__.'/weibo/saetv2.ex.class.php');

/**
 * 微博授权登陆
 */

class WeiBoComponent extends CApplicationComponent
{

    /**
     * 获取微博登录的url
     * @return type
     */
    public function getCodeUrl(){
        
    $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
    $code_url = $o->getAuthorizeURL( WB_CALLBACK_URL );
    return $code_url;
    
    }
    
    /**
     * 获取微博Token
     * @param type $code
     * @return boolean
     */
    public function getWeiBoToken($code){
        $keys = array();
	$keys['code'] = $code;
	$keys['redirect_uri'] = WB_CALLBACK_URL;
        $token = '';
	try {
         $oAuth = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
         $token = $oAuth->getAccessToken('code', $keys) ;
	} catch (Exception $e) {
            return false;   
	}
        $session = Yii::app()->session;
        $session['token'] = $token;
	setcookie('weibojs_'.$oAuth->client_id, http_build_query($token) );
        return $token;
    }

    /**
     * 获取用户基本信息
     * @param type $access_token
     */
    public function getUserInfo($access_token){
    $c = new SaeTClientV2( WB_AKEY , WB_SKEY , $access_token);
    $ms  = $c->home_timeline(); // done
    $uid_get = $c->get_uid();
    $uid = $uid_get['uid'];
    $user_message = $c->show_user_by_id( $uid);//根据ID获取用户等基本信息
    return $user_message;
    }

    /**
     * 是否绑定
     * @param $userid
     * @return int
     */
    public static function isBind($userid){
        $result = 0;
        if($userid){
            if(UserAuths::model()->findByAttributes(array('user_id'=>$userid,'app_type'=>3))){
                $result = 1;
            }
        }
        return $result;
    }
}

?>