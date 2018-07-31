<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class HrController extends CmainController
{

    /**
     * 身份验证
     */
    public function isHr(){
        return true;
    }

    /**
    * 验证时间是否有效
    */
    public function checkDateIsValid($date, $formats = array("Y-m-d", "Y/m/d", "Ymd", "Y-m-d H:i:s", "Y-m-d H:i")) {
        $unixTime = strtotime($date);
        if (!$unixTime) { 
            return false;
        }
        //校验日期的有效性，只要满足其中一个格式就OK
        foreach ($formats as $format) {
            if (date($format, $unixTime) == $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * 验证Api请求参数 失败将不执行Api控制器
     * @param array $paramConfig
     * @return array
     * @throws CException
     */
    protected function validateParams( $paramConfig = array() )
    {
        $validator = new ParamValidator;
        $paramConfig = $validator->validateConfig($paramConfig);

        $params = array();
        // validate params only defined in apiConfig
        foreach( $paramConfig as $paramName => $param ) {
            if( isset( $param['copyAs'] ) && $param['copyAs'] ){
                $param = $paramConfig[$param['copyAs']];
                $param['name'] = $paramName;
            }

            // Get param value according the request method.
            $requestMethod = isset( $param['requestMethod'] ) ? strtolower($param['requestMethod']) : '';
            in_array($requestMethod,$validator::$requestMethods) or $requestMethod = '';
            // Get param value
            $paramVal = $this->getParam( $paramName,$requestMethod );
            // 如果允许为空 跳过后续验证
            if( $paramVal == '' && $param['allowEmpty'] ) {
                $params[$paramName] = isset($param['defaultValue'])?$param['defaultValue']:'';
                continue;
            }
            // Validate required
            if( is_null($paramVal) ) {
                // cann't get required param
                if( isset( $param['required'] ) && $param['required'] ) {
                    $this->outputJson( $this->returnMsg('lessParams', '缺少'.($param['title'] !=''?$param['title']:$param['name']).'参数'));
                }else{
                    $paramVal = '';
                }
            }
            // Validate allowEmpty
            if( $paramVal === '' ){
                // Empty value
                if( isset( $param['allowEmpty'] ) && !$param['allowEmpty'] ){
                    $this->outputJson($this->returnMsg('errorParams', ($param['title'] !=''?$param['title']:$param['name']).'异常或为空'));
                }

                $paramVal = isset( $param['defaultValue'] ) ? $param['defaultValue'] : $paramVal;
            }

            // Validate data type
            if( isset($param['type']) && $param['type'] ){
                $typeStatus = $validator->validateDataType($paramVal,$param['type']);
                $typeStatus or $this->outputJson($this->returnMsg('typeError', ($param['title'] !=''?$param['title']:$param['name']).'类型错误'));
            }

            // length
            if( isset( $param['length'] ) && $param['length'] ){
                $lengthStatus = $validator->validateLength($paramVal, $param['length']);
                $lengthStatus or $this->outputJson($this->returnMsg('lengthError', ($param['title'] !=''?$param['title']:$param['name']).'长度错误,不能超过'.$param['length']));
            }

            // pattern
            if( isset( $param['pattern'] ) && $param['pattern'] != '' ) {
                $pregStarts = $validator->ctypeRegx($paramVal, $param['pattern']);
                $pregStarts or $this->outputJson($this->returnMsg('patternError', ($param['title'] !=''?$param['title']:$param['name']).'错误'));
            }


            // Save current param
            $params[$paramName] = $paramVal;
        }
        unset( $validator );

        return $params;
    } 

    /**
     * 根据请求方式获取请求参数
     * @param $name
     * @param string $requestMethod
     * @return mixed|null
     * @throws CException
     */
    private function getParam( $name, $requestMethod = '' )
    {
        switch( strtolower($requestMethod) ) {
            case 'get':
                $paramVal = Yii::app()->request->getQuery($name);
                break;
            case 'post':
                $paramVal = Yii::app()->request->getPost($name);
                break;
            default:
                $paramVal = Yii::app()->request->getParam($name);
                break;
        }

        return $paramVal;
    }
    /**
     * 获取文件后缀名
     * @param $name
     * @return string
     * @throws CException
     */     
    protected function fileExt($filename)
    {
        return $filename != ''?substr(strrchr($filename, '.'), 1):false;
    }
    /**
     * 时间差计算
     *
     * @param Timestamp $time
     * @return String Time Elapsed
     * @author Shelley Shyan
     * @copyright http://phparch.cn (Professional PHP Architecture)
     */
    function time2Units ($time)
    {
        $year   = floor($time / 60 / 60 / 24 / 365);
        $time  -= $year * 60 * 60 * 24 * 365;
        $month  = floor($time / 60 / 60 / 24 / 30);
        /*$time  -= $month * 60 * 60 * 24 * 30;
        $week   = floor($time / 60 / 60 / 24 / 7);
        $time  -= $week * 60 * 60 * 24 * 7;
        $day    = floor($time / 60 / 60 / 24);
        $time  -= $day * 60 * 60 * 24;
        $hour   = floor($time / 60 / 60);
        $time  -= $hour * 60 * 60;
        $minute = floor($time / 60);
        $time  -= $minute * 60;
        $second = $time;*/
        $elapse = '';

        /*$unitArr = array('年'  =>'year', '月'=>'month',  '周'=>'week', '天'=>'day',
            '小时'=>'hour', '分钟'=>'minute', '秒'=>'second'
        );*/
        $unitArr = array('年'  =>'year', '月'=>'month');

        foreach ( $unitArr as $cn => $u )
        {
            if ( $$u > 0 )
            {
                $elapse .= $$u . $cn;
                //break;
            }
        }

        return $elapse;
    }
    /**
     * 时间差计算, 最小单位为秒
     *
     * @param Timestamp $time
     * @return String Time Elapsed
     * @author Shelley Shyan
     * @copyright http://phparch.cn (Professional PHP Architecture)
     */
    function time2MinUnits ($time)
    {
        $year   = floor($time / 60 / 60 / 24 / 365);
        $time  -= $year * 60 * 60 * 24 * 365;
        $month  = floor($time / 60 / 60 / 24 / 30);
        $time  -= $month * 60 * 60 * 24 * 30;
        $week   = floor($time / 60 / 60 / 24 / 7);
        $time  -= $week * 60 * 60 * 24 * 7;
        $day    = floor($time / 60 / 60 / 24);
        $time  -= $day * 60 * 60 * 24;
        $hour   = floor($time / 60 / 60);
        $time  -= $hour * 60 * 60;
        $minute = floor($time / 60);
        $time  -= $minute * 60;
        $second = $time;
        $elapse = '';

        $unitArr = array('年'  =>'year', '月'=>'month',  '周'=>'week', '天'=>'day',
            '小时'=>'hour', '分钟'=>'minute', '秒'=>'second'
        );
        //$unitArr = array('年'  =>'year', '月'=>'month');

        foreach ( $unitArr as $cn => $u )
        {
            if ( $$u > 0 )
            {
                $elapse .= $$u . $cn;
                break;
            }
        }

        return $elapse;
    }

    /**
     * 时间格式化
     */
    static function formatDate($time)
    {
        $time_diff = time () - $time;
        if ($time_diff < 60) {
            $str = '刚刚';
        } elseif ($time_diff < 60 * 60) {
            $min = floor ( $time_diff / 60 );
            $str = $min . '分钟前';
        } elseif ($time_diff < 60 * 60 * 24) {
            $h = floor ( $time_diff / (60 * 60) );
            $str = $h . '小时前';
        } elseif ($time_diff < 60 * 60 * 24 * 3) {
            $d = floor ( $time_diff / (60 * 60 * 24) );
            if ($d == 1)
                $str = '昨天';
            else
                $str = '前天';
        } elseif ($time_diff < 60 * 60 * 24 * 7) {
            $d = floor ( $time_diff / (60 * 60 * 24) );
            $str = $d . '天前';
        } else {
            $str = date ( "Y-m-d", $time );
        }
        return $str;
    }

    /**
     * 获取HR所属公司ID
     * @return int
     */
    function getMyCompanyId()
    {
        $result = 0;
        if(Yii::app()->user->id){
            $model = SysUserHr::model()->findByPk(Yii::app()->user->id);
            if($model){
                $result = $model->COMPANY_ID;
            }
        }
        return $result;
    }
    
      /**
     * 验证手机
     * @param type $phone
     * @return type
     */
    public function validPhone($phone)
    {
        return  preg_match("/^1[0-9]{10}$/",$phone);
    }

    /**
     * @desc  im:十进制数转换成三十六机制数
     * @param (int)$num 十进制数
     * return 返回：三十六进制数
     */
    public function get_36char($num)
    {
        $num = intval($num);
        if ($num <= 0)
            return false;
        $charArr = array("0","1","2","3","4","5","6","7","8","9",'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $char = '';
        do {
            $key = ($num - 1) % 36;
            $char= $charArr[$key] . $char;
            $num = floor(($num - $key) / 36);
        } while ($num > 0);
        return $char;
    }

    public function verifyCaptcha($char)
    {
        $key = 'Yii.CCaptchaAction.'.Yii::app()->getId().'.'.$this->getUniqueId().'.'.$_COOKIE['PHPSESSID'];
        $serverCode = Yii::app()->cache->get($key);
        if($serverCode == $char){
            return true;
        }else{
            return '图形验证码错误';
        }
    }

    /**
     * 用户是否审核通过
     * @return bool
     */
    public function isUserAuditPassed()
    {
        $user = Yii::app()->user->getState("userinfo");
        if($user){
            switch($user['AUDIT_STATUS']){
                case SysUserHr::STATUS_UNCONFIRMED:
                    $this->outputJson($this->returnMsg('AuthError', '尚未填写认证资料'));
                    break;
                case SysUserHr::STATUS_AUDIT_FAILED:
                    $this->outputJson($this->returnMsg('AuthError', '认证未通过'));
                    break;
                case SysUserHr::STATUS_AUDIT_PASSED:
                    return true;
                    break;
                case SysUserHr::STATUS_PENDING:
                    $this->outputJson($this->returnMsg('AuthError', '认证已提交，等待客服'));
                    break;
            }
        }else{
            $this->outputJson($this->returnMsg('timeoutAuth'));
        }
    }
    
       /**
     * @param $array
     * @return array
     * 将对象装换成数组
     */
    function ObjectToArray($array) {
        if (is_object($array)) {
            $array = (array) $array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->ObjectToArray($value);
            }
        }
        return $array;
    }
}