<?php
define('API_DEBUG', true);
define('WEB_URL', 'http://api.scaffolding.com');
define('WECHAT_URL', 'http://api.scaffolding.com');
// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => '脚手架',
    'sourceLanguage' => 'en',
    'timeZone' => 'PRC',
    'language' => 'zh_cn',
    'defaultController' => 'platform',

    // preloading 'log' component
    'preload' => array('log'),

    'theme' => 'autumn',

    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.extensions.redis.*',
    ),

    'modules' => array(
        'v1_0',//1.0版本
        // uncomment the following to enable the Gii tool
        'gii' => array(
            'class' => 'system.gii.GiiModule',
            'password' => '1',
            // If removed, Gii defaults to localhost only. Edit carefully to taste.
            'ipFilters' => array('*', '::1'),
        ),

    ),

    // application components
    'components' => array(
        'SmsService'=>array(
            'class'=>'application.extensions.AliDaYu.ServiceProxy',
            'appkey'=>'12345678',
            'secretKey'=>'asdfasdfasdfasdfadsfadsfasdfasdf'
        ),
        'mailer' => array(
            'class' => 'application.extensions.mailer.EMailer',
            'pathViews' => 'application.views.email',
            'pathLayouts' => 'application.views.email.layouts'
        ),
        'execl' => array(
            'class' => 'application.extensions.PHPExcel.Autoloader',
        ),
        'mobileDetect' => array(
            'class' => 'ext.MobileDetect.MobileDetect'
        ),
        'user' => array(
            'class'=>'ScaffoldingWebUser',
            // enable cookie-based authentication
            'allowAutoLogin' => true,
            'loginUrl' => array('platform/login'),
        ),
      'weixin' => array(
            'class'=>'application.components.WeChatComponent',
        ),
      'weibo' => array(
            'class'=>'application.components.WeiBoComponent',
        ),
      'qq' => array(
            'class'=>'application.components.QqComponent',
        ),
        'redisQueue' => array(
            'class' => 'ext.redis.ARedisConnection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
            'password'=>'pass',
            'prefix' => '',
        ),
        'redisCache' => array(
            'class' => 'ext.redis.ARedisConnection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
            'password'=>'pass',
            'prefix' => 'scaffolding.cache.',
        ),
        'cache' => array(//高速缓存
            "class" => "ext.redis.ARedisCache",
            //"serializer"=>array("json_encode","json_decode"),
            'connection' => 'redisCache',
            'keyPrefix'=>'asdfasdfasdfasdfadsfasdfasdfadsf',
        ),
        'redisSession' => array(
            'class' => 'ext.redis.ARedisConnection',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 1,
            'password'=>'pass',
            'prefix' => 'scaffolding.session.',
        ),
        'cacheSession' => array(//存储Session
            "class" => "ext.redis.ARedisCache",
            //"serializer"=>array("json_encode","json_decode"),
            'connection' => 'redisSession',
            'keyPrefix'=>'asdfasdfasdfasdfadsfasdfasdfadsf',
        ),
        'session' => array(
            'class' => 'CCacheHttpSession',
            'autoStart' => true,
            'cacheID' => 'cacheSession',
            'timeout' => 1200,
            'cookieMode' => 'only',
            //  'cookieParams' => array('lifetime' => '1200','sessionName' => 'scaffolding', 'path' => '/', 'domain' => '.scaffolding.com', 'httponly' => '1'),
        ),
        'db' => array(
            'class' => 'DbConnectionMan',//扩展路径
            'connectionString' => 'mysql:host=localhost;dbname=api_scaffolding_yii11',//主数据库 写
            'emulatePrepare' => true,
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            // 'schemaCachingDuration' => 86400, // time in seconds
            'enableSlave' => true,//从数据库启用
            'slavesWrite' => false,//紧急情况 主数据库无法连接 启用从数据库 写功能
            'masterRead' => true,//紧急情况 从数据库无法连接 启用主数据库 读功能
            'slaves' => array(//从数据库
                array(   //slave1
                    'connectionString' => 'mysql:host=localhost;dbname=api_scaffolding',//主数据库 写
                    'emulatePrepare' => true,
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8',
                    // 'schemaCachingDuration' => 86400, // time in seconds 86400
                ),
            ),
        ),
        'sphinx' => array(
            'class' => 'application.extensions.DGSphinxSearch',
            'server' => '127.0.0.1',
            'port' => 9312,
            'maxQueryTime' => 3000,
            'enableProfiling'=>0,
            'enableResultTrace'=>0,
            'fieldWeights' => array(
                'name' => 10000,
                'keywords' => 100,
            ),
        ),
        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => false,//隐藏index.php
            'urlSuffix' => '.html',//后缀
            'rules' => array(
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),
        'errorHandler' => array(
            'errorAction' => 'platform/error',
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning,trace,info',
                    'categories' => 'login.*',
                    'logFile' => 'login.log',
                ),
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning,trace,info',
                    'categories' => 'sendmsg.*',
                    'logFile' => 'sendmsg.log',
                ),
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning,trace,info',
                    'categories' => 'register.*',
                    'logFile' => 'register.log',
                ),
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                    'categories' => 'curl.*',
                    'logFile' => 'curl.log',
                ),
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                    'categories' => 'sendMail.*',
                    'logFile' => 'sendMail.log',
                ),
            ),
        ),
        'curl' => array(
            'class'            => 'application.extensions.curl.Curl', //主文件
            'options' => array(/* additional curl options */),
        ),
    ),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
    'params' => array(
        // this is used in contact page
        'MsgTimeOut'=>300,
        'CodeTimeOut'=>300,
        'loginType'=>0,//0手机号，1手机号+邮箱+账号
        'encryptKey'=> "asdfadsfasdfadsf2asdfadsf" ,
        //http状态码
        'codeMessage' => array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        ),
        'returnMsg' => array(
            //common
            'success' => array('code' => 0, 'msg' => 'OK'),
            'failed' => array('code' => 1, 'msg' => 'FALSE'),
            'undefined' => array('code' => 10, 'msg' => '未定义错误'),
            'controllerserror' => array('code' => 100, 'msg' => '缺少控制器行为'),
            'lessParams' => array('code' => 2001, 'msg' => '缺少必须参数'),
            'typeError' => array('code' => 2002, 'msg' => '参数类型错误'),
            'lengthError' => array('code' => 2003, 'msg' => '参数长度错误'),
            'patternError' => array('code' => 2004, 'msg' => '参数错误'),
            'nodata' => array('code' => 32, 'msg' => '没有数据'),
            'limit' => array('code' => 5000, 'msg' => '您提交的太快了，请稍后再试'),
            'errorServer' => array('code' => 5001, 'msg' => '后台服务异常'),
            'errorAccount' => array('code' => 5002, 'msg' => '用户账号不存在'),
            'errorParams' => array('code' => 5118, 'msg' => '数据异常或为空'),
            'successAuth' => array('code' => 0, 'msg' => '登录成功'),
            'errorAuth' => array('code' => 5111, 'msg' => '验证服务异常'),
            'timeoutAuth' => array('code' => 5112, 'msg' => '身份异常请重新登录', 'value' => "/user/login"),
            'isLogin' => array('code' => 5003, 'msg' => '已经登录状态'),
            'isLogout' => array('code' => 5004, 'msg' => '已经成功退出'),
            'alreadyregister' => array('code' => 5005, 'msg' => '已经注册'),
            'noaccess' => array('code' => 5006, 'msg' => '无权限'),
            'codeError' => array('code' => 5007, 'msg' => '验证码错'),
            'AuthError' => array('code' => 5008, 'msg' => '验证失败'),
            'alreadAuth' => array('code' => 5009, 'msg' => '已经验证'),
            'alreadApply' => array('code' => 5010, 'msg' => '已经存在该记录操作'),
            'unEmail' => array('code' => 5015, 'msg' => 'EMAIL未验证'),
            'noMoney' => array('code' => 5016, 'msg' => '余额不足'),
            'alreadybuy' => array('code' => 5017, 'msg' => '此人已入职'),
            'begintimelt' => array('code' => 5018, 'msg' => '开始时间小于当天'),
            'endtimegt' => array('code' => 5019, 'msg' => '结束时间大于限定值'),
            'endtimeerror' => array('code' => 5020, 'msg' => '结束时间异常'),
            'notLogin' => array('code' => 5030, 'msg' => '账号尚未登陆过'),
            'unUserRefined' => array('code' => 5040, 'msg' => '用户信息待完善'),
        ),
        'QRcodePath'=>'/data/web/api.scaffolding.com/web/attachment/QRcode',
        'AuthFilePath'=>'/data/sharedisk/scaffolding/auth',
        'AuthHttpPath'=>'/scaffolding/auth',
        'AvatarFilePath'=>'/data/sharedisk/scaffolding/avatar',
        'AvatarHttpPath'=>'/scaffolding/avatar',
        'AttachmentFilePath'=>'/data/sharedisk/scaffolding/attachment',
        'AttachmentHttpPath'=>'/scaffolding/attachment',
        'image_upload_path' => '/data/sharedisk',
        'image_upload_url' => '',
        'mailList' => array(
            "306539332@qq.com",
        ),
        'offShoreMailList' => array(
            "306539332@qq.com",
        ),
        'hostUrl'=>'http://api.scaffolding.com',
        'v1Url'=>'http://api.scaffolding.com',
        'html2pdf'=>'/opt/wkhtmltox/bin/wkhtmltopdf',//Html转Pdf
        'sendCloud' => array(
            'apiUser' => 'apiUser',
            'apiKey' => 'apiKey',
        ),
        'sms.notify.queue' => 'sms.api.scaffolding.com',
    ),
);