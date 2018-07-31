<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{

    public $id;

    /**
     * Authenticates a user.
     * The example implementation makes sure if the username and password
     * are both 'demo'.
     * In practical applications, this should be changed to authenticate
     * against some persistent user identity storage (e.g. database).
     * @return boolean whether authentication succeeds.
     */
    public function authenticate($usertype=0)
    {
        $user = $this->getUser($usertype);

        if($user==null){
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }else{
                if ($user->password==md5(Yii::app()->params["encryptKey"].$this->password.$user->SALT)) {
                    $this->id = $user->ID;
                    //todo 判断登录后的用户身份加载用户的可选属性信息
                    $userinfo = null;
                    switch($usertype){
                        case 2:
                            $userinfo = SysUserHr::model()->findByPk($this->id);
                            break;
                    }

                    if(isset($userinfo)){
                        $users = array_merge($user->attributes,array('COMPANY_NAME'=>@$userinfo->COMPANY_NAME));
                        $users['avatar'] = $users['avatar']?:'';
                        $users['sex'] = $users['sex']?:'0';
                        $users['email'] = $users['email']?:'';
                        $users['city'] = $users['city']?:'0';
                        $users['is_bind_wechat'] = WeChatComponent::isBind($user->ID);
                        $users['is_bind_qq'] = QqComponent::isBind($user->ID);
                        $users['is_bind_weibo'] = WeiBoComponent::isBind($user->ID);
                        $user->last_login = time()*1000;
                        $user->save();
                        $this->setState("userinfo", $users);
                        $this->errorCode = self::ERROR_NONE;
                    }else{
                        $this->errorCode = self::ERROR_USERNAME_INVALID;
                    }
                } else {
                    $this->errorCode = self::ERROR_PASSWORD_INVALID;
                }
        }
        return !$this->errorCode;
    }

    //第三方登录
    public function authThirdParties($userid, $auth_type)
    {
        $user = SysUserBasic::model()->findByAttributes(array("ID"=>$userid,"STATUS"=>1,"USERTYPE"=>2));
        $user_auth = UserAuths::model()->find("user_id=:user_id and app_type=:app_type",array(':user_id'=>$userid, ':app_type'=>$auth_type));

        if($user==null || $user_auth==null){
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }else{
            $this->id = $user->ID;
            //todo 判断登录后的用户身份加载用户的可选属性信息
            $userinfo = SysUserHr::model()->findByPk($this->id);

            if(isset($userinfo)){
                $users = array_merge($user->attributes,array('COMPANY_NAME'=>@$userinfo->COMPANY_NAME));
                $users['avatar'] = $users['avatar']?:'';
                $users['sex'] = $users['sex']?:'0';
                $users['email'] = $users['email']?:'';
                $users['city'] = $users['city']?:'0';
                $users['is_bind_wechat'] = WeChatComponent::isBind($user->id);
                $users['is_bind_qq'] = QqComponent::isBind($user->id);
                $users['is_bind_weibo'] = WeiBoComponent::isBind($user->id);
                $users['third_party'] = array(
                    'auth_type' => $auth_type,
                    'nick_name' => $user_auth->nickname,
                    'avatar' => $user_auth->avatar,
                );
                $user->last_login = time()*1000;
                $user->save();
                $this->setState("userinfo", $users);
                $this->errorCode = self::ERROR_NONE;
            }else{
                $this->errorCode = self::ERROR_USERNAME_INVALID;
            }
        }
        return !$this->errorCode;
    }

    
    //第三方登录
    public function authCodeLogin($userid)
    {
        $user = SysUserBasic::model()->findByAttributes(array("ID"=>$userid,"STATUS"=>1,"USERTYPE"=>2));

        if($user==null){
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }else{
            $this->id = $user->ID;
            //todo 判断登录后的用户身份加载用户的可选属性信息
            $userinfo = SysUserHr::model()->findByPk($this->id);

            if(isset($userinfo)){
                $users = array_merge($user->attributes,array('COMPANY_NAME'=>@$userinfo->COMPANY_NAME));
                $users['avatar'] = $users['avatar']?:'';
                $users['sex'] = $users['sex']?:'0';
                $users['email'] = $users['email']?:'';
                $users['city'] = $users['city']?:'0';
                $users['is_bind_wechat'] = WeChatComponent::isBind($user->id);
                $users['is_bind_qq'] = QqComponent::isBind($user->id);
                $users['is_bind_weibo'] = WeiBoComponent::isBind($user->id);
                $users['third_party'] = array(
                    'auth_type' => $auth_type,
                    'nick_name' => $user_auth->nickname,
                    'avatar' => $user_auth->avatar,
                );
                $user->last_login = time();
                $user->save();
                $this->setState("userinfo", $users);
                $this->errorCode = self::ERROR_NONE;
            }else{
                $this->errorCode = self::ERROR_USERNAME_INVALID;
            }
        }
        return !$this->errorCode;
    }
    
    
    public function getUser($usertype=0)
    {
        $user = null;
        $loginType = Yii::app()->params["loginType"];
        if($loginType){
            $regex = '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[-_a-z0-9][-_a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,})$/i';
            if (preg_match($regex, $this->username)) {
                $user = SysUserBasic::model()->findByAttributes(array("email"=>$this->username,"user_type"=>$usertype));
            }elseif(preg_match('/^1[0-9]{10}$/',$this->username)){
                $user = SysUserBasic::model()->findByAttributes(array("phone"=>$this->username,"user_type"=>$usertype));
            }else{
                $user = SysUserBasic::model()->findByAttributes(array("account"=>$this->username,"user_type"=>$usertype));
            }
        }else{
            $user = SysUserBasic::model()->findByAttributes(array("phone"=>$this->username,"user_type"=>$usertype));
        }
        return $user;
    }

    public function isActive($usertype=0)
    {
        $user = $this->getUser($usertype);
        if($user==null){
            return false;
        }else{
            return $user->STATUS==1;
        }
    }

    public function getId()
    {
        return $this->id;
    }

}