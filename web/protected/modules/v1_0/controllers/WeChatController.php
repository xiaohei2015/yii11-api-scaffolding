<?php

/**
 * 微信登录
 */
class WeChatController extends HrController {

    public $userInfo;
    public $wxId;
    public $codeUrl = '/v3_0/weChat/callback';//未登录跳转 获取Code 页面
    public $redirectUrl = '/';//已登录直接跳转页面
    public $bindUrl = '/v3_0/weChat/bind';//用户中心绑定微信
    public $redirectBindUrl = '/center.html';//授权注册页面

    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
        );
    }

    public function accessRules() {
        return array(
            array('allow', // 常规
                'actions' => array(
                    "bind",
                    "login",
                    "callback",
                ),
                'users' => array('*'),
            ),
            array('allow', // 常规
                'actions' => array(
                    "bindLogin",
                ),
                'verbs' => array("GET"),
                'users' => array('*'),
            ),
            array('allow', // 常规
                'actions' => array(
                    "unbind",
                ),
                'verbs' => array("GET"),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * 微信登录
     * @return type
     */
    public function actionLogin() {
         //如果已登录，直接跳转至首页
        if (Yii::app()->user->id) {
            return $this->redirect('/'); 
        }
        
        $weixin = Yii::app()->weixin;
        $codeUrl = $weixin->getOAuthUrl(Yii::app()->params['hostUrl'].$this->codeUrl);
        if (Yii::app()->user->id) {
            $user = Yii::app()->user->getState("userinfo");
            if ($user["USERTYPE"] != 2) {
                return $this->redirect($codeUrl);
            } else {
                return $this->redirect($this->redirectUrl);  
            }
        } else {
            return $this->redirect($codeUrl);
        }
    }


    private function setWechatBindState()
    {
        $user = Yii::app()->user->getState("userinfo");
        $data = array_merge($user,array(
            "is_bind_wechat"=>1,
        ));
        Yii::app()->user->setState("userinfo", $data);
    }

    
    /**
     * 微信登录的回调
     */
    public function actionCallback()
    {
        $code = Yii::app()->request->getParam('code');
        $state = Yii::app()->request->getParam('state');
        if (!Yii::app()->cache->get($state)) {
            $this->outputJson($this->returnMsg('failed', 'state错误！'));
        }
        if (!$code) {
            $this->outputJson($this->returnMsg('failed', 'code错误！'));
        }
        //根据code 一步获取用户信息
        $weixin = Yii::app()->weixin;
        $userInfo = $weixin->getWeChatUserInfo($code);
        $state = md5(uniqid(rand(), TRUE));

        //获取用户身份成功
        if ($userInfo) {
            $data = [];
            $data['appUserId'] = $userInfo->unionid;
            $data['nickname'] = $userInfo->nickname;
            $data['avatar'] = $userInfo->headimgurl;
            $data['thirdType'] = 1;
            Yii::app()->cache->set($state, $data, 60 * 60);
            //创建第三方信息
            $userAuth = UserAuths::model()->findByAttributes(['app_user_id' => $userInfo->unionid]);

            //创建新用户
            if (!$userAuth) {
                $userAuth = new UserAuths();
                $userAuth->app_user_id = $userInfo->unionid;
                $userAuth->create_time = time();
                $userAuth->is_bind = 0;
                $userAuth->app_type = 1;
                $userAuth->nickname = $userInfo->nickname;
                $userAuth->avatar = $userInfo->headimgurl;
                $userAuth->update_time = time();
                $userAuth->save();
            }

            //更新头像
            if ($userAuth->avatar <> $userInfo->headimgurl || $userAuth->nickname <> $userInfo->nickname) {
                $userAuth->nickname = $userInfo->nickname;
                $userAuth->avatar = $userInfo->headimgurl;
                $userAuth->update_time = time();
                $userAuth->save();
            }

            //已绑定用户自动登录
            if ($userAuth && $userAuth->is_bind) {
                $identity = new UserIdentity($userAuth->user_id, '');
                $identity->authThirdParties($userAuth->user_id, $userAuth->app_type);
                if ($identity->errorCode === UserIdentity::ERROR_NONE) {
                    Yii::app()->user->login($identity, 3600 * 24 * 30);

                    $user = Yii::app()->user->getState("userinfo");
                    $data = array_merge($user, array(
                        "ENVIRONMENT" => $this->environment,
                    ));
                    //去除密码敏感信息
                    unset($data["password"]);
                    unset($data["salt"]);
                    //设置Cookie
                    Cookie::set('scaffolding_mem_id', $user['id'], 365 * 24 * 60 * 60);
                    return $this->redirect(Yii::app()->params['v3Url'].'/');
                }
            }
            //未登录用户跳转注册页
            return $this->redirect(Yii::app()->params['v3Url'].'/oauth-bind.html?state=' . $state);
        } else {
            //授权失败
            return $this->redirect('/404.html?msg=授权失败请重新点击授权按钮进行授权！');
        }
    }
    
     /**
     * 账号设置--微信绑定授权登录
     * @return type
     */
    public function actionBindLogin() {
        if(Yii::app()->user->id) {
            $weixin = Yii::app()->weixin;
            $codeUrl = $weixin->getOAuthUrl(Yii::app()->params['hostUrl'] . $this->bindUrl);
            return $this->redirect($codeUrl);
        }else{
            return $this->redirect(Yii::app()->params['v3Url'] . '/login.html');
        }
    }


    /**
     * 账号设置--微信绑定
     * @return type
     */
    public function actionBind()
    {
        $code = Yii::app()->request->getParam('code');
        $state = Yii::app()->request->getParam('state');
        //$session = Yii::app()->session;

        //if(!$session[$state]){
        if (!$state) {
            $this->outputJson($this->returnMsg('failed', 'state错误！'));
        }

        if (!$code) {
            $this->outputJson($this->returnMsg('failed', 'code错误！'));
        }

        //根据code 一步获取用户信息
        $weixin = Yii::app()->weixin;
        $userInfo = $weixin->getWeChatUserInfo($code);
        if (!$userInfo) {
            $this->outputJson($this->returnMsg('failed', '微信获取信息失败！'));
        }
        $userAuth = UserAuths::model()->findByAttributes(['app_user_id' => $userInfo->unionid]);
        if ($userAuth && $userAuth->is_bind == 1) {
            if (Yii::app()->user->id) {
                $this->setWechatBindState();
                return $this->redirect(Yii::app()->params['v3Url'] . $this->redirectBindUrl);
            }
        } else if (!$userAuth) {
            $userAuth = new UserAuths();
            $userAuth->app_user_id = $userInfo->unionid;
            $userAuth->create_time = time();
        }
        $userAuth->user_id = Yii::app()->user->id ? Yii::app()->user->id : 1;
        $userAuth->app_type = 1;
        $userAuth->nickname = $userInfo->nickname;
        $userAuth->avatar = $userInfo->headimgurl;
        $userAuth->update_time = time();
        $userAuth->is_bind = 1;
        if ($userAuth->save()) {
            $this->setWechatBindState();
            return $this->redirect(Yii::app()->params['v3Url'] . $this->redirectBindUrl);
        } else {
            return $this->redirect(Yii::app()->params['v3Url'] . $this->redirectBindUrl);
        }
    }
    
    
     /**
     * 账号设置--微信解绑
     * @return type
     */
    public function actionUnbind()
    {
        $userId = Yii::app()->user->id;
        $thirdUserId = Yii::app()->request->getParam('thirdUserId');
        $thirdUserId = $thirdUserId ? $thirdUserId : 2;
        $userAuth = UserAuths::model()->findByAttributes(['id' => $thirdUserId, 'user_id' => $userId, 'app_type' => 1]);

        if (!$userAuth) {
            $this->outputJson($this->returnMsg('failed', '未绑定该微信号，请重新解绑！'));
        }

        $userAuth->user_id = 0;
        $userAuth->update_time = time();
        $userAuth->is_bind = 0;
        if ($userAuth->save()) {
            $user = Yii::app()->user->getState("userinfo");
            $data = array_merge($user,array(
                "is_bind_wechat"=>0,
            ));
            Yii::app()->user->setState("userinfo", $data);
            $this->outputJson($this->returnMsg('success', '解绑成功！'));
        } else {
            $this->outputJson($this->returnMsg('failed', '解绑失败！'));
        }
    }
}
