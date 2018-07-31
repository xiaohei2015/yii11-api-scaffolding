<?php

/**
 * 用户
 */
class UserController extends HrController
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
                'actions' => array(
                    "login",
                    "authLogin",
                    "register",
                    "sendMsg",
                    "sendAuthMsg",
                    "verifyCode",
                    "forgetPwd",
                    "isRegister",
                    "verifyCaptcha",
                    "bind",
                    "bindRegister"
                ),
                'verbs' => array("POST"),
                'users' => array('*'),
            ),
            array('allow', // 常规
                'actions' => array(
                    'captcha',
                    "logout",
                    "isLogin",
                    "verifyMail",
                    "thirdInfo",
                    
                ),
                'verbs' => array("GET"),
                'users' => array('*'),
            ),
            array('allow', // 常规
                'actions' => array(
                    "resetPwd",
                    "editUser",
                    "refineUser",
                    "editPhone",
                    "editEmail",
                    "editAvatar",
                    "upload",
                    "sendEmailCode",
                    "userBindInfo",
                ),
                'verbs' => array("POST"),
                'users' => array('@'),
            ),
            array('allow', // 常规
                'actions' => array(
                    "bindWechat",
                    "bindStatus"
                ),
                'verbs' => array("GET"),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public function actions()
    {
        $code = substr(md5(time()),11,4);
        if(isset($_COOKIE['PHPSESSID'])){
            $key = 'Yii.CCaptchaAction.'.Yii::app()->getId().'.'.$this->getUniqueId().'.'.$_COOKIE['PHPSESSID'];
            Yii::app()->cache->set($key, $code, 300);
            Yii::log('IP:'.$this->userIp().', key:'.$code, "warning", "dispatch");
            return array(
                // captcha action renders the CAPTCHA image displayed on the contact page
                'captcha'=>array(
                    'class'=>'CCaptchaAction',
                    'backColor'=>0xFFFFFF,
                    'maxLength'=>'4',       // 最多生成几个字符
                    'minLength'=>'4',       // 最少生成几个字符
                    'height'=>'30',
                    'width'=>'62',
                    'padding'=>0,
                    'testLimit'=>3,
                    'fixedVerifyCode' => $code,
                ),
            );
        }else{
            $this->outputJson($this->returnMsg('failed', '客户端未开启COOKIE'));
        }
    }

    public function actionVerifyCaptcha()
    {
        $char = Yii::app()->request->getParam("char");
        $result = $this->verifyCaptcha($char);
        if(true === $result){
            $this->outputJson($this->returnMsg('success'));
        }else{
            $this->outputJson($this->returnMsg('failed', $result));
        }
    }


    /**
     * 用户登录
     */
    public function actionLogin()
    {
        $model = new LoginForm();
        $model->username = Yii::app()->request->getParam("account");
        $model->password = Yii::app()->request->getParam("password");
        $model->usertype = 2;
        $model->rememberMe = Yii::app()->request->getParam("rememberMe");
        if ($model->validate() &&  $model->login()) {
            $user = Yii::app()->user->getState("userinfo");
            $data = array_merge($user,array(
                "environment"=>$this->environment,
            ));

            //去除密码敏感信息
            unset($data["password"]);
            unset($data["salt"]);
            //设置Cookie
            Cookie::set('scaffolding_mem_id', $user['id'], 365*24*60*60);
            $this->outputJson($this->returnMsg('success',$data));
        } else {
            $this->outputJson($this->returnMsg('failed', $this->getReturnError($model->getErrors(),true)));
        }
    }


    /**
     * 验证码登录
     */
    public function actionAuthLogin()
    {
        $paramConfig = array();
        $paramConfig[] = ParamValidator::stringParam('account',array('title'=>'手机号码'));
        $paramConfig[] = ParamValidator::stringParam('captcha', array('title'=>'图形验证码'));
        $paramConfig[] = ParamValidator::numberParam('code', array('title'=>'手机验证码'));
        $params = $this->validateParams($paramConfig);

        if(!$this->validPhone($params['account'])){
            $this->outputJson($this->returnMsg('failed','手机号码错误'));
        }

        //图形验证码
        $result = $this->verifyCaptcha($params['captcha']);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }

        //验证码
        $result = MsgAuthManager::verifyCode($params['account'],$params['code']);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('code'=>$result)));
        }

        //帐号是否存在
        if(!$model=SysUserBasic::model()->findByAttributes(array("phone"=>$params['account'],"user_type"=>2))){
            $this->outputJson($this->returnMsg('failed','手机号不存在，请先注册'));
        }

        //开始事务
        //$transaction = Yii::app()->db->beginTransaction();
        try {
            $identity = new UserIdentity($model->ID, '');
            $identity->authCodeLogin($model->ID);

            if ($identity->errorCode === UserIdentity::ERROR_NONE) {
                Yii::app()->user->login($identity, 3600 * 24 * 30);

                $user = Yii::app()->user->getState("userinfo");
                $data = array_merge($user, array(
                    "environment" => $this->environment,
                ));
                //去除密码敏感信息
                unset($data["password"]);
                unset($data["salt"]);
                //设置Cookie
                Cookie::set('scaffolding_mem_id', $user['id'], 365*24*60*60);
                $this->outputJson($this->returnMsg('success', $data));
            }else{
                $this->outputJson($this->returnMsg('failed', '验证失败，请重试'));
            }
        } catch (Exception $e) {
            //如果操作失败, 数据回滚
            //$transaction->rollback();
            $this->outputJson($this->returnMsg('failed', $e->getMessage()));
        }
    }


    /**
     * 用户是否登录
     */
    public function actionIsLogin()
    {
        if (Yii::app()->user->id) {
            $user = Yii::app()->user->getState("userinfo");

            if($user["user_type"]!=2){
                $this->outputJson($this->returnMsg('noaccess'));
            }

            $data = array_merge($user,array(
                "environment"=>$this->environment,
            ));
            //去除密码敏感信息
            unset($data["password"]);
            unset($data["salt"]);
            $this->outputJson($this->returnMsg('success',$data));
        } else {
            $this->outputJson($this->returnMsg('timeoutAuth'));
        }
    }


    /**
     *注册接口
     */
    public function actionRegister()
    {
        $email =  trim(Yii::app()->request->getParam("email"));
        $password1 = trim(Yii::app()->request->getParam("password1"));
        $password2 = trim(Yii::app()->request->getParam("password2"));
        $phone =  trim(Yii::app()->request->getParam("phone"));
        $code = Yii::app()->request->getParam("code");
        //$name =  trim(Yii::app()->request->getParam("name"));
        $company_name =  trim(Yii::app()->request->getParam("company_name"));
        $captcha =  trim(Yii::app()->request->getParam("captcha"));

        if($phone==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写手机号码"));
        }
        if($code==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写短信验证码"));
        }
        if($captcha==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写图形验证码"));
        }
        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }
        //验证码
        $result = MsgAuthManager::verifyCode($phone,$code);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('code'=>$result)));
        }

        if($email==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写email"));
        }
        if($password1==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写密码"));
        }
        if($password2==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写确认密码"));
        }
        if(strlen($password1)<6 || strlen($password1)>20){
            $this->outputJson($this->returnMsg('failed', '密码必须是6-20位！'));
        }
        if($password1 != $password2){
            $this->outputJson($this->returnMsg('failed', '两次输入的密码不一致'));
        }
        /*if($name==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写姓名"));
        }*/
        if($company_name==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写企业名称"));
        }

        //账号是否注册过
        $err_is_reg = array();
        $user = SysUserBasic::model()->findByAttributes(array("phone"=>$phone,"user_type"=>2));
        if($user){
            $err_is_reg['phone']='您输入的手机号码已经注册！';
        }
        $mailuser = SysUserBasic::model()->findByAttributes(array("email"=>$email,"user_type"=>2));
        if($mailuser){
            $err_is_reg['email']='您输入的邮箱已经注册！';
        }
        if($err_is_reg){
            $this->outputJson($this->returnMsg('alreadyregister', $err_is_reg));
        }

        //开始事务
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $t = time();
            $model = new SysUserBasic();
            $model->account = uniqid();
            $model->phone = $phone;
            $model->user_type = 2;
            $model->salt = md5(Yii::app()->params["encryptKey"] . time());
            $model->password = md5(Yii::app()->params["encryptKey"] . $password1 . $model->salt);
            $model->email = $email;
            $model->add_time = $t;
            if (!$model->save()) {
                throw new Exception($this->getReturnError($model->getErrors()));
            }

            $userhr = new SysUserHr();
            $userhr->user_id = $model->ID;
            $userhr->company_name = trim($company_name);
            if (!$userhr->save()) {
                throw new Exception($this->getReturnError($userhr->getErrors()));
            }

            //提交事务会真正的执行数据库操作
            $transaction->commit();

            $identity = new UserIdentity($model->phone, $password1);
            $identity->authenticate(2);

            if ($identity->errorCode === UserIdentity::ERROR_NONE) {
                Yii::app()->user->login($identity, 3600 * 24 * 30);

                $user = Yii::app()->user->getState("userinfo");
                $data = array_merge($user, array(
                    "environment" => $this->environment,
                ));
                //去除密码敏感信息
                unset($data["password"]);
                unset($data["salt"]);
                //设置Cookie
                Cookie::set('scaffolding_mem_id', $user['id'], 365*24*60*60);
                $this->outputJson($this->returnMsg('success', $data));
            }
        } catch (Exception $e) {
            //如果操作失败, 数据回滚
            $transaction->rollback();
            $this->outputJson($this->returnMsg('failed', $e->getMessage()));
        }
    }


    /**
     * 登出
     */
    public function actionLogout()
    {
        Yii::app()->user->logout();
        $this->outputJson($this->returnMsg('success'));
        if (Yii::app()->request->isAjaxRequest) {
            if (Yii::app()->user->returnUrl == "/") {
                $this->outputJson($this->returnMsg('success',"/index.html"));
            } else {
                $this->outputJson($this->returnMsg('success',Yii::app()->user->returnUrl));
            }
        }else{
            if (Yii::app()->user->returnUrl == "/") {
                $this->redirect(array("/index"));
            } else {
                $this->redirect(Yii::app()->user->returnUrl);
            }
        }
    }


    /**
     * 发送验证短信
     * 1. 如果传入phone，则发送短信给phone
     * 2. 如果没有传入phone，则发送短信给当前用户的手机号
     */
    public function actionSendMsg()
    {
        $phone = trim(Yii::app()->request->getParam("phone"));
        $captcha =  trim(Yii::app()->request->getParam("captcha"));
        if($captcha==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写图形验证码"));
        }

        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }
        if($phone != ''){
            if(!preg_match('/^1[0-9]{10}$/',$phone)){
                $this->outputJson($this->returnMsg('failed', '您输入的手机号码有误！'));
            }
            $result = MsgAuthManager::sendMsg($phone);
            if(true === $result){
                $this->outputJson($this->returnMsg('success'));
            }else{
                $this->outputJson($this->returnMsg('failed', $result));
            }
        }elseif(Yii::app()->user->id){
            $userinfo = Yii::app()->user->getState("userinfo");
            if(!preg_match('/^1[0-9]{10}$/',$userinfo['phone'])){
                $this->outputJson($this->returnMsg('failed', '手机号码格式有误，请修改您的手机号码！'));
            }
            $result = MsgAuthManager::sendMsg($userinfo['phone']);
            if(true === $result){
                $this->outputJson($this->returnMsg('success'));
            }else{
                $this->outputJson($this->returnMsg('failed', $result));
            }
        }else{
            $this->outputJson($this->returnMsg('failed', '请填写手机号码！'));
        }
    }


    /**
     * 发送验证短信
     */
    public function actionSendAuthMsg()
    {
        $phone = trim(Yii::app()->request->getParam("phone"));
        $captcha =  trim(Yii::app()->request->getParam("captcha"));
        if($captcha==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写图形验证码"));
        }
        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }
        if($phone != ''){
            if(!preg_match('/^1[0-9]{10}$/',$phone)){
                $this->outputJson($this->returnMsg('failed', '您输入的手机号码有误！'));
            }
            $result = MsgAuthManager::sendMsg($phone);
            if(true === $result){
                $this->outputJson($this->returnMsg('success'));
            }else{
                $this->outputJson($this->returnMsg('failed', $result));
            }
        }else{
            $this->outputJson($this->returnMsg('failed', '请填写手机号码！'));
        }
    }


    /**
     * 验证码验证
     */
    public function actionVerifyCode()
    {
        $phone = trim(Yii::app()->request->getParam("phone"));
        $code = trim(Yii::app()->request->getParam("code"));
        if($code == ''){
            $this->outputJson($this->returnMsg('lessParams', '验证码不能为空！'));
        }
        if($phone != ''){
            if(!preg_match('/^1[0-9]{10}$/',$phone)){
                $this->outputJson($this->returnMsg('failed', '您输入的手机号码有误！'));
            }
            $result = MsgAuthManager::verifyCode($phone,$code);
            if(true === $result){
                $this->outputJson($this->returnMsg('success'));
            }else{
                $this->outputJson($this->returnMsg('failed', $result));
            }
        }elseif(Yii::app()->user->id){
            $userinfo = Yii::app()->user->getState("userinfo");
            if(!preg_match('/^1[0-9]{10}$/',$userinfo['phone'])){
                $this->outputJson($this->returnMsg('failed', '手机号码格式有误，请修改您的手机号码！'));
            }
            $result = MsgAuthManager::verifyCode($userinfo['phone'],$code);
            if(true === $result){
                $this->outputJson($this->returnMsg('success'));
            }else{
                $this->outputJson($this->returnMsg('failed', $result));
            }
        }else{
            $this->outputJson($this->returnMsg('failed', '请填写手机号码！'));
        }
    }


    /**
     * 重置密码
     */
    public function actionResetPwd()
    {
        $old_password = trim(Yii::app()->request->getParam("old_password"));
        $new_password = trim(Yii::app()->request->getParam("new_password"));
        $confirm_password = trim(Yii::app()->request->getParam("confirm_password"));
        $code = trim(Yii::app()->request->getParam("code"));
        $captcha =  trim(Yii::app()->request->getParam("captcha"));

        if($captcha==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写图形验证码"));
        }
        if($code==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写短信验证码"));
        }
        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }
        //短信验证码
        $userinfo = Yii::app()->user->getState("userinfo");
        if(!preg_match('/^1[0-9]{10}$/',$userinfo['phone'])){
            $this->outputJson($this->returnMsg('failed', '手机号码格式有误，请修改您的手机号码！'));
        }
        $result = MsgAuthManager::verifyCode($userinfo['phone'],$code);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('code'=>$result)));
        }

        //一致性验证
        if($new_password != $confirm_password){
            $this->outputJson($this->returnMsg('failed', '新密码和确认密码不一致！'));
        }
        //长度验证
        if(strlen($new_password)<6 || strlen($new_password)>20){
            $this->outputJson($this->returnMsg('failed', '密码必须是6-20位！'));
        }
        if($old_password == $new_password){
            $this->outputJson($this->returnMsg('failed', '新密码和当前密码不能相同！'));
        }

        //原密码验证
        $userinfo = Yii::app()->user->getState("userinfo");
        $identity = new UserIdentity($userinfo['phone'], $old_password);
        $identity->authenticate(2);
        if ($identity->errorCode !== UserIdentity::ERROR_NONE) {
            $this->outputJson($this->returnMsg('failed', '您输入的原密码有误！'));
        }

        $model = SysUserBasic::model()->findByPk(Yii::app()->user->id);
        $model->salt = md5(Yii::app()->params["encryptKey"] . time());
        $model->password = md5(Yii::app()->params["encryptKey"] . $new_password . $model->salt);
        if ($model->save()) {
            Yii::app()->user->logout();
            $this->outputJson($this->returnMsg('success'));
        }else{
            $this->outputJson($this->returnMsg('failed', $this->getReturnError($model->getErrors())));
        }
    }


    /**
     * 忘记密码
     */
    public function actionForgetPwd()
    {
        $phone = trim(Yii::app()->request->getParam("phone"));
        $new_password = trim(Yii::app()->request->getParam("new_password"));
        $confirm_password = trim(Yii::app()->request->getParam("confirm_password"));
        $code = trim(Yii::app()->request->getParam("code"));
        $captcha =  trim(Yii::app()->request->getParam("captcha"));

        if($phone == ''){
            $this->outputJson($this->returnMsg('failed', '请输入手机号码！'));
        }
        if(!preg_match('/^1[0-9]{10}$/',$phone)){
            $this->outputJson($this->returnMsg('failed', '您输入的手机号码有误！'));
        }
        if($code==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写短信验证码"));
        }
        if($captcha==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写图形验证码"));
        }
        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }
        //短信验证码
        $result = MsgAuthManager::verifyCode($phone,$code);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('code'=>$result)));
        }

        //一致性验证
        if($new_password != $confirm_password){
            $this->outputJson($this->returnMsg('failed', '新密码和确认密码不一致！'));
        }
        //长度验证
        if(strlen($new_password)<6 || strlen($new_password)>20){
            $this->outputJson($this->returnMsg('failed', '密码必须是6-20位！'));
        }

        if($model = SysUserBasic::model()->findByAttributes(array('phone'=>$phone,'user_type'=>2))){
            $model->salt = md5(Yii::app()->params["encryptKey"] . time());
            $model->password = md5(Yii::app()->params["encryptKey"] . $new_password . $model->salt);
            if ($model->save()) {
                $identity = new UserIdentity($model->phone, $new_password);
                $identity->authenticate(2);

                if ($identity->errorCode === UserIdentity::ERROR_NONE) {
                    Yii::app()->user->login($identity, 3600 * 24 * 30);

                    $user = Yii::app()->user->getState("userinfo");
                    $data = array_merge($user, array(
                        "environment" => $this->environment,
                    ));
                    //去除密码敏感信息
                    unset($data["password"]);
                    unset($data["salt"]);
                    $this->outputJson($this->returnMsg('success', $data));
                }
            }else{
                $this->outputJson($this->returnMsg('failed', $this->getReturnError($model->getErrors())));
            }
        }else{
            $this->outputJson($this->returnMsg('failed', array('phone'=>'尚未建立此账号，请先注册！')));
        }
    }


    /**
     * 修改用户信息
     */
    public function actionEditUser()
    {
        $avatar = Yii::app()->request->getParam("avatar");
        $name = Yii::app()->request->getParam("name");
        $position_name = Yii::app()->request->getParam("position_name");

        $model = SysUserBasic::model()->findByPk(Yii::app()->user->id);
        $modelhr = SysUserHr::model()->findByPk(Yii::app()->user->id);
        if($name=="" && $position_name==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写需要修改的信息！"));
        }
        if($avatar!=""){
            $model->AVATAR = trim($avatar);
        }
        if($name!=""){
            $model->NAME = trim($name);
        }
        if($position_name!=""){
            $modelhr->POSITION_NAME = trim($position_name);
        }

        $transaction = Yii::app()->db->beginTransaction();
        try {
            if (!$model->save()) {
                throw new Exception($this->getReturnError($model->getErrors()));
            }
            if (!$modelhr->save()) {
                throw new Exception($this->getReturnError($modelhr->getErrors()));
            }

            //提交事务会真正的执行数据库操作
            $transaction->commit();

            $user = array_merge(Yii::app()->user->getState("userinfo"),$model->attributes,array('POSITION_NAME'=>$modelhr->POSITION_NAME));
            $user['POSITION_NAME'] = $user['POSITION_NAME']?:'';
            Yii::app()->user->setState("userinfo", $user);
            $data = array_merge($user, array(
                "environment" => $this->environment,
            ));
            //去除密码敏感信息
            unset($data["password"]);
            unset($data["salt"]);
            $this->outputJson($this->returnMsg('success', $data));

        } catch (Exception $e) {
            //如果操作失败, 数据回滚
            $transaction->rollback();
            $this->outputJson($this->returnMsg('failed', $e->getMessage()));
        }
    }


    /**
     * 修改手机
     */
    public function actionEditPhone()
    {
        $old_phone = Yii::app()->request->getParam("old_phone");
        $old_code = Yii::app()->request->getParam("old_code");
        $new_phone = Yii::app()->request->getParam("new_phone");
        $new_code = Yii::app()->request->getParam("new_code");
        $captcha =  trim(Yii::app()->request->getParam("captcha"));

        if($old_phone==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写原手机号码！"));
        }
        if($old_code==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写原手机验证码！"));
        }
        if($new_phone==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写新手机号码！"));
        }
        if($new_code==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写新手机验证码！"));
        }
        if($captcha==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写图形验证码"));
        }

        if($old_phone == $new_phone){
            $this->outputJson($this->returnMsg('failed', '您输入的新手机和原手机号码相同！'));
        }

        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }

        //原手机号码正确性检查
        $userinfo = Yii::app()->user->getState("userinfo");
        if($userinfo['phone'] != $old_phone){
            $this->outputJson($this->returnMsg('failed', '原手机号码填写有误！'));
        }

        //原手机验证码验证
        $result = MsgAuthManager::verifyCode($old_phone,$old_code);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('old_code'=>'原手机号'.$result)));
        }

        //新手机验证码验证
        $result = MsgAuthManager::verifyCode($new_phone,$new_code);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('new_code'=>'新手机号'.$result)));
        }

        //新手机正确性检查
        if(!preg_match('/^1[0-9]{10}$/',$new_phone)){
            $this->outputJson($this->returnMsg('failed', '您输入的新手机号码有误！'));
        }
        if(SysUserBasic::model()->findByAttributes(array('phone'=>$new_phone,'user_type'=>2))){
            $this->outputJson($this->returnMsg('failed', array('new_phone'=>'对不起，您输入的新手机号码已被注册！')));
        }

        $transaction = Yii::app()->db->beginTransaction();
        try {
            $model = SysUserBasic::model()->findByPk(Yii::app()->user->id);
            $model->phone = trim($new_phone);
            if(!$model->save()){
                throw new Exception($this->getReturnError($model->getErrors()));
            }

            //提交事务会真正的执行数据库操作
            $transaction->commit();

            $user = Yii::app()->user->getState("userinfo");
            $data = array_merge($user,array(
                "phone"=>$new_phone,
            ));
            Yii::app()->user->setState("userinfo", $data);

            $this->outputJson($this->returnMsg('success'));

        } catch (Exception $e) {
            //如果操作失败, 数据回滚
            $transaction->rollback();
            $this->outputJson($this->returnMsg('failed', $e->getMessage()));
        }
    }


    /**
     * 绑定微信号
     */
    public function actionBindWechat()
    {
        $criteria = new CDbCriteria();
        $criteria->condition = "status = 0 and add_time>=".(time()-Yii::app()->params["CodeTimeOut"])*1000;
        $criteria->compare('uid',Yii::app()->user->id);
        $criteria->order = "add_time DESC";
        $model_code = SysCodeAuthLog::model()->find($criteria);
        if($model_code){
            $code = $model_code->code;
            $filename =  $code.".png";
            $img = $this->str_split_unicode($code,4);
            $pathinfo = Yii::app()->params["AuthFilePath"]."/".$img[0]."/".$img[1]."/".$img[2]."/".$img[3]."/".$img[4]."/".$img[5]."/".$img[6]."/".$img[7];
        }else{
            //生成验证码code
            $code = md5(Yii::app()->params["encryptKey"] . time());
            $model = new SysCodeAuthLog();
            $model->uid = Yii::app()->user->id;
            $model->code = $code;
            $model->status = 0;
            $model->add_time = time() * 1000;
            $model->save();

            //生成二维码
            $filename =  $code.".png";
            $img = $this->str_split_unicode($code,4);
            $url = WECHAT_URL.'/v1_0/center/pcBind?bindUid='.Yii::app()->user->id.'&bindCode='.$code;
            $pathinfo = Yii::app()->params["AuthFilePath"]."/".$img[0]."/".$img[1]."/".$img[2]."/".$img[3]."/".$img[4]."/".$img[5]."/".$img[6]."/".$img[7];
            mkdir($pathinfo,0777,true);
            $this->generateQRcode($pathinfo."/".$filename,$url,6,true);
        }

        $file=$pathinfo."/".$filename;
        $fp=fopen($file,'rb',0);
        $data = array(
            'image'=>base64_encode(fread($fp,filesize($file))),
        );
        fclose($fp);

        $this->outputJson($this->returnMsg('success', $data));
    }


    /**
     * 获取是否绑定成功
     */
    public function actionBindStatus()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('uid',Yii::app()->user->id);
        $criteria->order = "add_time DESC";
        $model_code = SysCodeAuthLog::model()->find($criteria);
        if($model_code){
            if($model_code->status == 0 && (time() - $model_code->add_time/1000)>Yii::app()->params["CodeTimeOut"]){
                $this->outputJson($this->returnMsg('failed', '二维码已过期，请刷新页面！'));
            }else{
                if($model_code->status == 1){
                    $user = Yii::app()->user->getState("userinfo");
                    //是否绑定微信号
                    $data = array_merge($user,array(
                        "is_bind_wechat"=>1,
                    ));
                    Yii::app()->user->setState("userinfo", $data);
                }
                $this->outputJson($this->returnMsg('success', $model_code->attributes));
            }
        }else{
            $this->outputJson($this->returnMsg('failed', '请您刷新绑定二维码！'));
        }

    }


    /**
     * 修改头像接口
     */
    public function actionEditAvatar()
    {
        $avatar = CUploadedFile::getInstanceByName('avatar');
        if(!$avatar){
            $this->outputText($this->returnMsg('failed','请选择一张图片!'));
        }

        if(!in_array(strtolower($avatar->getExtensionName()),array("jpg","gif","bmp","jpeg","png"))){
            $this->outputText($this->returnMsg('failed','图片格式有误!'));
        }
        if($avatar->getSize() > 5*1024*1024){
            $this->outputText($this->returnMsg('failed','图片大小不能超过5M!'));
        }
        $code = md5(Yii::app()->params["encryptKey"] .Yii::app()->user->id. time());
        $img = $this->str_split_unicode($code,4);
        $pathinfo = Yii::app()->params["AvatarFilePath"]."/".$img[0]."/".$img[1]."/".$img[2]."/".$img[3]."/".$img[4]."/".$img[5]."/".$img[6]."/".$img[7];
        mkdir($pathinfo,0755,true);
        $newname = $code.'.'.strtolower($avatar->getExtensionName());
        $status = $avatar->saveAs($pathinfo.'/'.$newname);
        if($status){
            $model = SysUserBasic::model()->findByPk(Yii::app()->user->id);
            $httpname = Yii::app()->params["AvatarHttpPath"]."/".$img[0]."/".$img[1]."/".$img[2]."/".$img[3]."/".$img[4]."/".$img[5]."/".$img[6]."/".$img[7]."/".$newname;
            $model->AVATAR = $httpname;
            $model->save();

            $user = Yii::app()->user->getState("userinfo");
            $data = array_merge($user,array(
                "AVATAR"=>$httpname,
            ));
            Yii::app()->user->setState("userinfo", $data);
            $this->outputText($this->returnMsg('success',$data));
        }else{
            $this->outputText($this->returnMsg('failed','上传失败，请重试!'));
        }
    }


    /**
     * 判断是否注册
     */
    public function actionIsRegister()
    {
        $account = Yii::app()->request->getParam("account");
        if($account==""){
            $this->outputJson($this->returnMsg('lessParams',"请输入您的手机号！"));
        }
        if (!preg_match('/^1[0-9]{10}$/',$account)) {
            $this->outputJson($this->returnMsg('errorParams',"手机号格式有误，请重新输入！"));
        }

        $user = SysUserBasic::model()->findByAttributes(array("phone"=>$account,"user_type"=>2));
        if($user){
            if($user->last_login == 0){
                $this->outputJson($this->returnMsg('notLogin'));
            }else
                $this->outputJson($this->returnMsg('alreadyregister'));
        }else
            $this->outputJson($this->returnMsg('success',"尚未注册"));
    }
    
     
    /**
     * 获取第三方信息
     */
    public function actionThirdInfo()
    {
        $state = Yii::app()->request->getParam("state");
        if($state==""){
            $this->outputJson($this->returnMsg('lessParams',"请输入身份信息！"));
        }
        $data = Yii::app()->cache->get($state);
         if($data){
            $this->outputJson($this->returnMsg('success',$data));
         }else{
            $this->outputJson($this->returnMsg('failed','用户信息不存在！'));
         }
    }
    
    
     /**
     *注册接口
     */
    public function actionBindRegister()
    {
        $email =  trim(Yii::app()->request->getParam("email"));
        $password = trim(Yii::app()->request->getParam("password"));
        $validPassword = trim(Yii::app()->request->getParam("validPassword"));
        $phone =  trim(Yii::app()->request->getParam("phone"));
        $code = Yii::app()->request->getParam("code");
        $name =  trim(Yii::app()->request->getParam("name"));
        $company_name =  trim(Yii::app()->request->getParam("company_name"));
        $captcha = trim(Yii::app()->request->getParam("captcha"));
        $appUserId = trim(Yii::app()->request->getParam("appUserId"));
        $thirdType = trim(Yii::app()->request->getParam("thirdType"));
        if($phone==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写手机号码"));
        }
        if(!$this->validPhone($phone)){
            $this->outputJson($this->returnMsg('lessParams',"请填写正确的手机号码"));
        }
        if($code==""){
            $this->outputJson($this->returnMsg('failed',"请填写短信验证码！"));
        }
        //验证码
        $result = MsgAuthManager::verifyCode($phone,$code);
        if(true !== $result){
            $this->outputJson($this->returnMsg('failed', array('code'=>$result)));
        }

        if($email==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写email"));
        }
        if($password==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写密码"));
        }
        if(strlen($password)<6 || strlen($password)>20){
            $this->outputJson($this->returnMsg('failed', '密码必须是6-20位！'));
        }
        if($password<>$validPassword){
            $this->outputJson($this->returnMsg('failed', '两次密码不一致！'));
        }
//        if($name==""){
//            $this->outputJson($this->returnMsg('lessParams',"请填写姓名"));
//        }
        if($company_name==""){
            $this->outputJson($this->returnMsg('lessParams',"请填写企业名称"));
        }
        if(!$appUserId){
          $this->outputJson($this->returnMsg('lessParams',"请输入第三方唯一标示！"));  
        }
         if(!$thirdType){
           $this->outputJson($this->returnMsg('lessParams',"请输入第三方类型")); 
        }
        //账号是否注册过
        $err_is_reg = array();
        $user = SysUserBasic::model()->findByAttributes(array("phone"=>$phone,"user_type"=>2));
        if($user){
            $err_is_reg['phone']='您输入的手机号码已经注册！';
        }
        $mailuser = SysUserBasic::model()->findByAttributes(array("email"=>$email,"user_type"=>2));
        if($mailuser){
            $err_is_reg['email']='您输入的邮箱已经注册！';
        }
        if($err_is_reg){
            $this->outputJson($this->returnMsg('alreadyregister', $err_is_reg));
        }

        //开始事务
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $t = time() * 1000;
            $model = new SysUserBasic();
            $model->account = uniqid();
            $model->phone = $phone;
            $model->user_type = 2;
            $model->salt = md5(Yii::app()->params["encryptKey"] . time());
            $model->password = md5(Yii::app()->params["encryptKey"] . $password . $model->salt);
            $model->NAME = trim($name);
            $model->email = $email;
            $model->add_time = $t;
            if (!$model->save()) {
                throw new Exception($this->getReturnError($model->getErrors()));
            }

            //绑定第三方
            if(!$thirdModel = UserAuths::model()->findByAttributes(['app_user_id'=>$appUserId,'app_type'=>$thirdType])){
                 throw new Exception('第三方唯一识别码错误！');
            }else{
                $thirdModel->user_id = $model->ID;
                $thirdModel->is_bind = 1;
                $thirdModel->update_time = time();
                if(!$thirdModel->save()){
                   throw new Exception($this->getReturnError($thirdModel->getErrors()));  
                }
            }
            
            $userhr = new SysUserHr();
            $userhr->user_id = $model->ID;
            $userhr->company_name = trim($company_name);
            if (!$userhr->save()) {
                throw new Exception($this->getReturnError($userhr->getErrors()));
            }

            //商机
            Business::addBizFromPcReg(array_merge($_POST,array('hr_id'=>Yii::app()->user->id)));

            //提交事务会真正的执行数据库操作
            $transaction->commit();

            $identity = new UserIdentity($model->phone, $password);
            $identity->authenticate(2);

            if ($identity->errorCode === UserIdentity::ERROR_NONE) {
                Yii::app()->user->login($identity, 3600 * 24 * 30);

                $user = Yii::app()->user->getState("userinfo");
                $data = array_merge($user, array(
                    "environment" => $this->environment,
                ));
                //去除密码敏感信息
                unset($data["password"]);
                unset($data["salt"]);
                //设置Cookie
                Cookie::set('scaffolding_mem_id', $user['id'], 365*24*60*60);
                $this->outputJson($this->returnMsg('success', $data));
            }
        } catch (Exception $e) {
            //如果操作失败, 数据回滚
            $transaction->rollback();
            $this->outputJson($this->returnMsg('failed', $e->getMessage()));
        }
    }


    /**
     * 绑定第三方用户
     */
    public function actionBind()
    {
        $appUserId = Yii::app()->request->getParam("appUserId");//第三方唯一标示
        $thirdType = Yii::app()->request->getParam("thirdType");//第三方类型
        $userAuth = UserAuths::model()->findByAttributes(['app_user_id' => $appUserId, 'app_type' => $thirdType]);
        if (!$userAuth) {
            $this->outputJson($this->returnMsg('failed', '第三方身份不存在！'));
        }
        $model = new LoginForm();
        $model->username = Yii::app()->request->getParam("account");
        $model->password = Yii::app()->request->getParam("password");
        $model->usertype = 2;
        $model->rememberMe = Yii::app()->request->getParam("rememberMe");
        if ($model->validate() && $model->login()) {

            $user = Yii::app()->user->getState("userinfo");
            $data = array_merge($user, array(
                "environment" => $this->environment,
            ));
            $isBindUser = UserAuths::model()->findByAttributes(['app_user_id' => $appUserId, 'is_bind' => 1]);
            if ($isBindUser) {
                Yii::app()->user->logout();
                $this->outputJson($this->returnMsg('failed', '已绑定平台账号，请先解绑该账号！'));
            }
            $isBindThird = UserAuths::model()->findByAttributes(['user_id' => Yii::app()->user->id, 'app_type' => $thirdType]);
            if ($isBindThird) {
                Yii::app()->user->logout();
                $this->outputJson($this->returnMsg('failed', '您已绑定同类型账号，请先解绑该账号！'));
            }

            //绑定第三方
            $userAuth->user_id = Yii::app()->user->id;
            $userAuth->update_time = time();
            $userAuth->is_bind = 1;
            if (!$userAuth->save()) {
                $this->outputJson($this->returnMsg('failed', '登录失败，请稍后再试！'));
            }

            //第三方登录状态
            $user = Yii::app()->user->getState("userinfo");
            if($thirdType == 1){
                $data = array_merge($user,["is_bind_wechat"=>1]);
            }else if($thirdType == 2){
                $data = array_merge($user,["is_bind_qq"=>1]);
            }else if($thirdType == 3){
                $data = array_merge($user,["is_bind_weibo"=>1]);
            }
            Yii::app()->user->setState("userinfo", $data);

            //去除密码敏感信息
            unset($data["password"]);
            unset($data["salt"]);
            //设置Cookie
            Cookie::set('scaffolding_mem_id', $user['id'], 365 * 24 * 60 * 60);
            $this->outputJson($this->returnMsg('success', $data));
        } else {
            $this->outputJson($this->returnMsg('failed', $this->getReturnError($model->getErrors(), true)));
        }
    }


    /**
     * 发送验证邮箱
     */
    public function actionSendEmailCode()
    {
        $email = trim(Yii::app()->request->getParam("email"));
        $captcha = trim(Yii::app()->request->getParam("captcha"));
        if ($captcha == "") {
            $this->outputJson($this->returnMsg('lessParams', "请填写图形验证码"));
        }

        //图形验证码
        $result = $this->verifyCaptcha($captcha);
        if (true !== $result) {
            $this->outputJson($this->returnMsg('failed', array('captcha'=>$result)));
        }
        if ($email != '') {
            $regex = '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[-_a-z0-9][-_a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,})$/i';
            if (!preg_match($regex, $email)) {
                $this->outputJson($this->returnMsg('failed', '您输入的邮箱格式有误！'));
            }
            $code = strval(rand(100000, 999999));
            //记录验证码至数据库
            $model = new SysCodeAuthLog();
            $model->uid = Yii::app()->user->id;
            $model->add_time = time() * 1000;
            $model->code = $code;
            $model->status = 0;
            @$model->save();
            $to = array($email);
            $sub = array(
                '%code%' => array($code),
                '%date%' => array(date('Y年m月d日'))
            );
            $result = SendMailManager::sendMailWithTemplate($to, $sub, "dp_php_email_code");
            if (true === $result) {
                $this->outputJson($this->returnMsg('success'));
            } else {
                $this->outputJson($this->returnMsg('failed', $result));
            }
        } else {
            $this->outputJson($this->returnMsg('failed', '请填写邮箱！'));
        }
    }


    /**
     * 用户是否登录
     */
    public function actionUserBindInfo()
    {
        if (Yii::app()->user->id) {
            $user = Yii::app()->user->getState("userinfo");

            if ($user["user_type"] != 2) {
                $this->outputJson($this->returnMsg('noaccess'));
            }

            $isBindWeChat = UserAuths::model()->findByAttributes(['user_id' => Yii::app()->user->id, 'app_type' => 1, 'is_bind' => 1]);
            $isBindQQ = UserAuths::model()->findByAttributes(['user_id' => Yii::app()->user->id, 'app_type' => 2, 'is_bind' => 1]);
            $isBindWeiBo = UserAuths::model()->findByAttributes(['user_id' => Yii::app()->user->id, 'app_type' => 3, 'is_bind' => 1]);
            $data ['thirdList']['wechat'] = [
                'isBind' => $isBindWeChat ? 1 : 0,
                'backendId' => $isBindWeChat ? $isBindWeChat->id : 0,
                'nickname' => $isBindWeChat ? $isBindWeChat->nickname : '',
                'app_type' => 1,
            ];
            $data ['thirdList']['qq'] = [
                'isBind' => $isBindQQ ? 1 : 0,
                'backendId' => $isBindQQ ? $isBindQQ->id : 0,
                'nickname' => $isBindQQ ? $isBindQQ->nickname : '',
                'app_type' => 2,
            ];
            $data ['thirdList']['weibo'] = [
                'isBind' => $isBindWeiBo ? 1 : 0,
                'backendId' => $isBindWeiBo ? $isBindWeiBo->id : 0,
                'nickname' => $isBindWeiBo ? $isBindWeiBo->nickname : '',
                'app_type' => 3,
            ];
            $this->outputJson($this->returnMsg('success', $data));
        } else {
            $this->outputJson($this->returnMsg('timeoutAuth'));
        }
    }

    /**
     * 上传附件接口
     */
    public function actionUpload()
    {
        $avatar = CUploadedFile::getInstanceByName('attachment');
        if(!$avatar){
            $this->outputText($this->returnMsg('failed','请选择一张图片!'));
        }

        if(!in_array(strtolower($avatar->getExtensionName()),array("jpg","gif","bmp","jpeg","png"))){
            $this->outputText($this->returnMsg('failed','图片格式有误!'));
        }
        if($avatar->getSize() > 5*1024*1024){
            $this->outputText($this->returnMsg('failed','图片大小不能超过5M!'));
        }
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $pathinfo = Yii::app()->params["AvatarFilePath"]."/".$year."/".$month."/".$day;
        if (!is_dir($pathinfo)) {
            mkdir($pathinfo, 0755, true);
        }
        $newname = 'img_'.Yii::app()->user->id.'_'.floor(microtime(true)*1000).'.'.strtolower($avatar->getExtensionName());
        $status = $avatar->saveAs($pathinfo.'/'.$newname);
        if($status){
            $httpname = Yii::app()->params["AvatarHttpPath"]."/".$year."/".$month."/".$day."/".$newname;
            $this->outputJson($this->returnMsg('success',$httpname));
        }else{
            $this->outputJson($this->returnMsg('failed','上传失败，请重试!'));
        }
    }
}