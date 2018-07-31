<?php
/**
 *首页 
 */

class HomeController extends HrController
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
                    "home"
                ),
                'verbs' => array("GET"),
                'users' => array('*'),
            ),
            array('allow', // 常规
                'actions' => array(
                    "customers",
                ),
                'verbs' => array("POST"),
                'users' => array('*'),
            ),
            array('allow', // 常规
                'actions' => array(
                    ""
                ),
                'verbs' => array("POST"),
                'expression' => array($this, 'isHr'),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /*
     * 首页接口
     */
    public function actionHome()
    {
        $keyName = 'home_info';
        $data_arr = Yii::app()->cache->get($keyName);
        if (!$data_arr) {
            $t = 60 * 60;

            //工作地点
            $data_arr['city'] = ['不限',''];

            //工作年限
            $worktimes = SysStatic::model()->findAll("status=1 order by id ASC");
            if ($worktimes) {
                foreach ($worktimes as $worktime) {
                    $data_arr['worktime'][] = $worktime->value;
                }
            }
            Yii::app()->cache->set($keyName, $data_arr, $t);
        }
        if ($data_arr) {
            $this->outputJson($this->returnMsg("success", $data_arr));
        } else {
            $this->outputJson($this->returnMsg("nodata"));
        }
    }


    //获取客户列表
    public function actionCustomers()
    {
        $page = Yii::app()->request->getParam("page");
        $pages = Yii::app()->request->getParam("pages");
        $page = isset($page) && (int)$page !=0?(int)$page:1;
        $pages = isset($pages) && (int)$pages !=0?(int)$pages:10;
        $_GET['page'] = $page;
        $_GET['pages'] = $pages;
        $name = 'customers_list_'.$page.'_'.$pages;
        $data_arr = Yii::app()->cache->get($name);
        if(!$data_arr){
            $t = 60*60;
            $criteria = new CDbCriteria();
            $criteria->compare("status", 1);
            $criteria->order = "SORT DESC";
            $count = SyshCompanyInfo::model()->count($criteria);
            $pager = new CPagination($count);
            $pager->pageSize = $pages;
            $pager->applyLimit($criteria);

            if($results = SyshCompanyInfo::model()->findAll($criteria)) {
                foreach($results as $k => $v){
                    $data = array(
                        'company_id'=>(int)$v->id,
                        'name'=>$v->fullname,
                        'logo'=>$v->logo?$v->logo:''
                    );
                    $data_arr['list'][] = $data;
                }
            }else{
                $data_arr['list'] = array();
            }
            $data_arr["page"]=array(
                "page"=>(int)$page,
                "pages"=>(int)$pages,
                "countpage"=>(int)ceil($count/$pages),
                "count"=>(int)$count,
            );
            Yii::app()->cache->set($name, $data_arr,$t);
        }
        $this->outputJson($this->returnMsg('success',$data_arr));
    }
}