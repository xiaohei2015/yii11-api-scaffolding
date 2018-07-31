<?php
include_once(__dir__.'/qq/API/qqConnectAPI.php');
/**
 * QQ 授权登陆
 */

class QqComponent extends CApplicationComponent
{

    public static function getInsatnce($access_token = "", $openid = ""){
        $qc = new QC($access_token,$openid);
        return $qc;    
    }
   

    public static function getUserInfo($access_token = "", $openid = ""){
           $qc = new QC($access_token,$openid);
           return $qc->get_user_info();
    }

    /**
     * 是否绑定
     * @param $userid
     * @return int
     */
    public static function isBind($userid){
        $result = 0;
        if($userid){
            if(UserAuths::model()->findByAttributes(array('user_id'=>$userid,'app_type'=>2))){
                $result = 1;
            }
        }
        return $result;
    }
}

?>