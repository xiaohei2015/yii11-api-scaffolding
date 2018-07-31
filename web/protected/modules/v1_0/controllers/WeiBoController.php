<?php

/**
 * 微博登录
 */
class WeiBoController extends HrController {

    public $redirectRegistUrl = '/';//授权注册页面
    public $redirectBindUrl = '/user/uctenr';//授权注册页面    

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
                    "unbind"
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
     * 微博登录
     * @return type
     */
    public function actionLogin() {
        //如果已登录，直接跳转至首页
        if (Yii::app()->user->id) {
            return $this->redirect('/'); 
        }
        $weibo = Yii::app()->weibo;
        $baseUrl = $weibo->getCodeUrl();
        return $this->redirect($baseUrl);
    }


    private function setWeiboBindState()
    {
        $user = Yii::app()->user->getState("userinfo");
        $data = array_merge($user,array(
            "is_bind_weibo"=>1,
        ));
        Yii::app()->user->setState("userinfo", $data);
    }


    /**
     * 微博登录的回调
     */
    public function actionCallback()
    {
        //用户取消授权处理
        $error_code = Yii::app()->request->getParam('error_code');
        if($error_code==21330){//access_denied
            return $this->redirect(Yii::app()->params['v3Url'] . '/login.html');
        }

        //获取授权码
        $code = Yii::app()->request->getParam('code');
        $session = Yii::app()->session;
        $weibo = Yii::app()->weibo;
        $token = $weibo->getWeiBoToken($code);
        $userInfo = $weibo->getUserInfo($token['access_token']);
        $state = md5(uniqid(rand(), TRUE));

        //授权成功并获取用户信息
        if ($userInfo && isset($userInfo['id'])) {
            //缓存用户信息，用于注册和绑定
            $data = [];
            $data['appUserId'] = $userInfo['id'];
            $data['nickname'] = $userInfo['screen_name'];
            $data['avatar'] = $userInfo['profile_image_url'];
            $data['thirdType'] = 3;
            Yii::app()->cache->set($state, $data, 60 * 60);


            //获取第三方信息
            $userAuth = UserAuths::model()->findByAttributes(['app_user_id' => $userInfo['id'], 'app_type' => 3]);

            //创建新用户
            if (!$userAuth) {
                $userAuth = new UserAuths();
                $userAuth->app_user_id = $userInfo['id'];
                $userAuth->access_token = $token['access_token'];
                $userAuth->create_time = time();
                $userAuth->app_type = 3;

                //如用户登录，则直接绑定微博
                if (Yii::app()->user->id) {
                    $userAuth->user_id = Yii::app()->user->id;
                    $userAuth->is_bind = 1;
                } else {
                    $userAuth->is_bind = 0;
                }
                $userAuth->nickname = $userInfo['screen_name'];
                $userAuth->avatar = $userInfo['profile_image_url'];
                $userAuth->update_time = time();
                $userAuth->save();
                if (Yii::app()->user->id) {
//             return $this->redirect(Yii::app()->params['hostUrl'].$this->redirectBindUrl);
                    $this->setWeiboBindState();
                    return $this->redirect(Yii::app()->params['v3Url'] . '/center.html');
                }
            } else {
                if (Yii::app()->user->id) {
                    $userAuth->user_id = Yii::app()->user->id;
                    $userAuth->is_bind = 1;
                    $userAuth->update_time = time();
                    $userAuth->save();
                    $this->setWeiboBindState();
                    return $this->redirect(Yii::app()->params['v3Url'] . '/center.html');
//             return $this->redirect(Yii::app()->params['hostUrl'].$this->redirectBindUrl);  
                }
            }

            //更新头像
            if ($userAuth->avatar <> $userInfo['profile_image_url'] || $userAuth->nickname <> $userInfo['screen_name']) {
                $userAuth->nickname = $userInfo['screen_name'];
                $userAuth->avatar = $userInfo['profile_image_url'];
                $userAuth->update_time = time();
                $userAuth->save();
            }

            //如果用户已经登录，则跳转到账号绑定的页面
            if (Yii::app()->user->id) {
                $this->setWeiboBindState();
                return $this->redirect(Yii::app()->params['v3Url'] . '/center.html');
                //return $this->redirect(Yii::app()->params['hostUrl'].$this->redirectBindUrl);
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
            return $this->redirect(Yii::app()->params['v3Url'] . '/oauth-bind.html?' . "?state=" . $state);
        } else {
            //授权失败
            return $this->redirect('/404.html?msg=授权失败请重新点击授权按钮进行授权！');
        }

    }
    
       /**
     * 账号设置--微博绑定授权登录
     * @return type
     */
    public function actionBindLogin() {
        if(Yii::app()->user->id){
            $weibo = Yii::app()->weibo;
            $baseUrl = $weibo->getCodeUrl();
            return $this->redirect($baseUrl);
        }else{
            return $this->redirect(Yii::app()->params['v3Url'] . '/login.html');
        }
    }
    
    
     /**
     * 账号设置--微博解绑
     * @return type
     */
    public function actionUnbind()
    {
        $userId = Yii::app()->user->id;
        $thirdUserId = Yii::app()->request->getParam('thirdUserId');
        $thirdUserId = $thirdUserId ? $thirdUserId : 4;
        $userAuth = UserAuths::model()->findByAttributes(['id' => $thirdUserId, 'user_id' => $userId, 'app_type' => 3]);

        if (!$userAuth) {
            $this->outputJson($this->returnMsg('failed', '未绑定该微博账号，请重新解绑！'));
        }

        $userAuth->user_id = 0;
        $userAuth->update_time = time();
        $userAuth->is_bind = 0;
        if ($userAuth->save()) {
            $user = Yii::app()->user->getState("userinfo");
            $data = array_merge($user,array(
                "is_bind_weibo"=>0,
            ));
            Yii::app()->user->setState("userinfo", $data);
            $this->outputJson($this->returnMsg('success', '解绑成功！'));
        } else {
            $this->outputJson($this->returnMsg('failed', '解绑失败！'));
        }
    }
}
