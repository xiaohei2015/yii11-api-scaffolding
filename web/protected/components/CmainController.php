<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class CmainController extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/home';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs=array();

    public $environment=1;

    public $isME=false;

    public function init()
    {
        $this->environment = $this->getEnvironment();
        $this->isME = (Yii::app()->mobileDetect->isMobile()||Yii::app()->mobileDetect->isTablet())?true:false;
        //处理angularjs传值问题
        if(isset($_SERVER['CONTENT_TYPE'])){
            $content_type_args = explode(';', $_SERVER['CONTENT_TYPE']);
            if ($content_type_args[0] == 'application/json')
                $_POST = json_decode(file_get_contents('php://input'),true);
        }
    }

    /**
     * json 输出，支持jsonp
     * @param $data
     */
    public function outputJson($data)
    {
        header('Content-type: application/json');
        header("Cache-Control: no-cache, must-revalidate");
        $json =  CJSON::encode($data);
        if(@$_GET['jscallback']){
            echo $_GET['jscallback'] . ' (' . $json . ');';
        }else if(@$_POST['jscallback']){
            echo $_POST['jscallback'] . ' (' . $json . ');';
        }else{
            echo $json;
        }
        Yii::app()->end();
    }

    /**
     * text 输出
     * @param $data
     */
    public function outputText($data)
    {
        header('Content-type: text/html');
        header("Cache-Control: no-cache, must-revalidate");
        echo CJSON::encode($data);
        Yii::app()->end();
    }

    public function  getEnvironment(){
        $envStr = GetHostByName($_SERVER['SERVER_NAME']);
        if($envStr=="192.168.14.215"){
            return 3;
        }else if($envStr=="192.168.14.216"){
            return 2;
        }else{
            return 1;
        }
    }


    /**
     * 数组的维数
     *
     * @param $arr
     * @return int
     */
    public function getmaxdim($arr)
    {
        if(!is_array($arr)){
            return 0;
        } else{
            $max1 = 0;
            foreach($arr as $item1){
                $t1 = $this->getmaxdim($item1);
                if( $t1 > $max1) $max1 = $t1;
            }
            return $max1 + 1;
        }
    }

    /**
     * 判断是否启用绝对路径
     * @param $path
     * @return string
     */
    public function getURLpath($path){
        if($path){
            if(false===strpos(strtolower($path), 'http://')){
                $path=Yii::app()->params["AttachmentURL"].$path;
            }
            return $path;
        }else{
            return "";
        }
    }

    /**
     * 批量判断是否启用绝对路径
     * @param $path
     * @return string
     */
    public function getURLpaths($path){
        if($path){
            $paths=explode(",",$path);
            foreach($paths as $v){
                if($v==""){
                    continue;
                }
                $arr[]=$this->getURLpath($v);
            }
            return implode(",",$arr);
        }else{
            return "";
        }
    }


    /**
     *
     * 获取返回码 code,msg
     *
     * @param $key
     * @param $data     获取返回 value
     * @return null
     */
    public function returnMsgLimit($key,$data=null)
    {
        $returnMsg  = Yii::app()->params['returnMsg'];
        $codeMsg    = isset($returnMsg[$key]) ? $returnMsg[$key] :$returnMsg['undefined'];

        if(!$data){
            return $codeMsg;
        }
        $data=$this->getOutData($data);
        return array_merge($codeMsg,$data);
    }

    /**
     * 组合数组
     */
    public  function getOutData($data){
        //分页
        //数组为空
        $dataType = 0;
        if(is_string($data)){
            $dataType = 0;
        }else if(is_array($data)){
            if (count($data) == count($data, 1)) {
                $dataType = 1;
            } else {
                $dataType = 2;
                foreach($data as $v){
                    if(is_array($v)){
                        $dataType = 3;
                        break;
                    }
                }
            }
        }else{
            $data = "object";
        }
        switch($dataType)
        {
            case 0://字符串
                if($data==null){
                    $data="";
                }
                return array('value'=>$data);
                break;
            case 1://一维数组
                foreach($data as $k=>$v){
                    if(is_array($v)){
                        $data[$k]=$this->getOutData($v);
                    }else{
                        if($v==null){
                            $v="";
                        }
                        $data[$k]=$v;
                    }
                }
                return array('item'=>$data );
                break;
            case 2://二维数组 list
                $header = array_keys($data[0]);
                $rows = array();
                $i=0;
                foreach ($data as $k => $v) {
                    $ii=0;
                    foreach($v as $kk=>$vv) {
                        if ($vv == null) {
                            $data[$i][$ii]="";
                        }
                        $ii++;
                    }
                    $rows[] = @array_values($v);
                    $i++;
                }
                $listData = array('data' => $rows, 'header' => $header);
                return array('list' => $listData);
                break;
            case 3://混合二维数组 list
                $header = array();
                $rows = array();
                if(isset($data[0])){
                    foreach($data[0] as $kk=>$vv){
                        $header[]=$kk;
                    }
                }else{
                    return "";
                }

                $i=0;
                foreach($data as $k=>$v){
                    $ii=0;
                    foreach($v as $kk=>$vv){
                        if(is_array($vv)){
                            $rows[$i][$ii]=$this->getOutData($vv);
                        }else{
                            $rows[$i][$ii]=$vv;
                        }
                        $ii++;
                    }
                    $i++;
                }
                $listData = array('data'=>$rows,'header'=>$header);
                return array('list'=>$listData);
                break;
        }
    }



    /**
     *
     * 获取返回码 code,msg
     *
     * @param $key
     * @param $data     获取返回 value
     * @return null
     */
    public function returnMsg($key,$data=null)
    {
        $returnMsg  = Yii::app()->params['returnMsg'];
        $codeMsg    = isset($returnMsg[$key]) ? $returnMsg[$key] :$returnMsg['undefined'];

        if($data === null){
            return $codeMsg;
        }

        $dim = $this->getmaxdim($data);
        if(is_array($data)){
            $data = array_change_key_case($data);
        }

        switch($dim)
        {
            case 0:
                return array_merge($codeMsg,array('data'=>$data ));
                break;
            case 1:
                return array_merge($codeMsg,array('data'=>$data ));
                break;
            case $dim>=2:
                return array_merge($codeMsg,array('data'=>$data));
        }
    }

    /**
     * 建立文件夹
     * @param $dir
     * @param int $mode
     * @return bool
     */
    function dmkdir($dir, $mode = 0777)
    {
        if (!is_dir($dir)) {
            $this->dmkdir(dirname($dir));
            $oldumask = umask(0);
            @mkdir($dir, $mode ,true);
            umask($oldumask);
            @touch($dir . '/index.htm');
            @chmod($dir . '/index.htm', 0777);
        }
        return true;
    }


    /**
     * Model object->array
     */
    public function modelObject2Array($data)
    {
        if($data){
            foreach($data as $item)
                $rows[] = $item->attributes;
            return $rows;
        }
        return $data;
    }

    /**
     * 返回错误信息
     * @param $error
     * @param bool|false $all
     * @return null
     */
    public function getReturnError($error,$all = false)
    {
        if(empty($error)){
            return null;
        }else{
            if($all){
                foreach ($error as $k => $v) {
                    $errors[$k] = $v[0];
                }
                return $errors;
            }else{
                foreach ($error as $k => $v) {
                    return  $v[0];
                }
            }
        }
    }


    /**
     * 用户的ip
     * @return string
     */
    public function userIp()
    {
        $IPaddress = '127.0.0.1';
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $IPaddress = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $IPaddress = getenv("HTTP_CLIENT_IP");
            } else {
                $IPaddress = getenv("REMOTE_ADDR");
            }
        }

        if (strstr($IPaddress, ','))
        {
            $ips = explode(',', $IPaddress);
            $IPaddress = $ips[0];
        }

        return $IPaddress;
    }


    /**
     * 微妙订单号生成
     * @return string
     */
    function generate_order() {
        $time = gettimeofday();
        $datetime = date("YmdHis",$time["sec"]);
        $usec = $time["usec"];
        return $datetime.substr($usec,0,5);
        //return $datetime.$usec.sprintf('%02s', rand(0, 99));
    }


    /**
     * 将unicode字符串按传入长度分割成数组
     * @param  string  $str 传入字符串
     * @param  integer $l   字符串长度
     * @return mixed      数组或false
     */
    function str_split_unicode($str, $l = 0) {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }


    /**
     * 生成二维码
     */
    public function generateQRcode($filepath, $value, $size = 6, $withlogo = false)
    {
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = $size;//生成图片大小

        //生成二维码图片
        QRcode::png($value, $filepath, $errorCorrectionLevel, $matrixPointSize, 2);
        if ($withlogo) {
            $logo = Yii::app()->params["QRcodePath"] . "/" . 'logo_s.png';
            if ($logo !== FALSE) {
                $QR = $filepath;
                $QR = imagecreatefromstring(file_get_contents($QR));
                $logo = imagecreatefromstring(file_get_contents($logo));
                $QR_width = imagesx($QR);//二维码图片宽度
                $QR_height = imagesy($QR);//二维码图片高度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度
                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = $logo_height / $scale;
                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                    $logo_qr_height, $logo_width, $logo_height);
                //输出图片
                imagepng($QR, $filepath);
            }
        }
    }

    /**
     * 图片路径算法
     *
     * 参数说明：
     * $image: e.g. 320ba2b40f0348b282c7181a564ecfdf.jpg
     * $real_path: e.g. /data/web/api.scaffolding.com/web/attachment/activity
     * $access_path: e.g. /attachment/activity
     */
    public function getImageFullPath($image, $real_path, $access_path)
    {
        $image_arr = explode('.',$image);
        if (!strlen($image_arr[0])==32) {
            return "/404.html";
        }
        $img = $this->str_split_unicode($image_arr[0],4);

        if(file_exists($real_path."/".$img[0]."/".$img[1]."/".$img[2]."/".$img[3]."/".$img[4]."/".$img[5]."/".$img[6]."/".$img[7]."/".$image)){
            return $access_path."/".$img[0]."/".$img[1]."/".$img[2]."/".$img[3]."/".$img[4]."/".$img[5]."/".$img[6]."/".$img[7]."/".$image;
        }else{
            return "/404.html";
        }
    }

    /*
     * trim数组
     */
    function TrimArray($arr){
        if (!is_array($arr)){ return $arr; }
        while (list($key, $value) = each($arr)){
            if(is_array($value)){
                $arr[$key] = $this->TrimArray($value);
            }else{
                $arr[$key] = trim($value);
            }
        }
        return $arr;
    }
}