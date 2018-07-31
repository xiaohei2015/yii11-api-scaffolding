<?php

/**
 * 订单
 */
class OrderController extends HrController
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
                    "submit",
                    "del",
                ),
                'verbs' => array("POST"),
                'expression' => array($this, 'isHr'),
                'users' => array('@'),
            ),array('allow', // 常规
                'actions' => array(
                    "list"
                ),
                'verbs' => array("GET"),
                'expression' => array($this, 'isHr'),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }
    //列表
    public function actionList()
    {
        $type = Yii::app()->request->getParam("type");
        $page = Yii::app()->request->getParam("page");
        $pages = Yii::app()->request->getParam("pages");
        $page = isset($page) && (int)$page !=0?(int)$page:1;
        $pages = isset($pages) && (int)$pages !=0?(int)$pages:5;
        $_GET['page'] = $page;
        $_GET['pages'] = $pages;  
        $type = isset($type)?$type:0;
        is_numeric($type) or $this->outputJson($this->returnMsg('failed',"类型有误!"));
        $criteria = new CDbCriteria(); 
        if($type == 1){
            $criteria->condition = " status in (1,2,3)";
        }else if($type ==2){
            $criteria->condition = " status in (7)";
        }else if($type ==3){
            $criteria->condition = " status in (8)";
        }else{
            $criteria->condition = " status > 0";
        }
        $criteria->compare("hr_id", Yii::app()->user->id);
        $criteria->compare("source", 0);
        $criteria->order = "add_time DESC";
        $count = Order::model()->count($criteria);
        $pager = new CPagination($count);
        $pager->pageSize=$pages;
        $pager->applyLimit($criteria);   
        if($results = Order::model()->findAll($criteria)) {
            foreach ($results as $v) {
                $data = array(
                    'id'=>(int)$v->id,
                );
                $data_arr['list'][] = $data;                    
            }
        }else{
            $data_arr['list'] = array();
        }
        $data_arr["page"]=array(
            "page"=>(int)$page?intval($page):1,
            "pages"=>(int)$pages,
            "countpage"=>(int)ceil($count/$pages),
            "count"=>(int)$count,
        );                           
        $this->outputJson($this->returnMsg('success',$data_arr));  
    }

    //提交
    public function actionSubmit()
    {
        $paramConfig = array(); 
        $paramConfig[] = ParamValidator::jsonParam('record_list');
        $params = $this->validateParams($paramConfig);
        $record_list = @json_decode($params['record_list'],true);
        $record_list = array_filter($record_list);
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $order = new Order();
            $order->hr_id = Yii::app()->user->id;
            $order->status = 2;
            $order->add_time = time()*1000;
            $order->update_time = time()*1000;
            if (!$order->save()) {
                throw new Exception($this->getReturnError($order->getErrors()));
            }
            foreach($record_list as $k => $v){
                $subs = new OrderSubs();
                $subs->order_id = $order->id;
                $subs->record_id = $v['record_id'];
                $subs->status = 1;
                $subs->add_time = time()*1000;
                $subs->update_time = time()*1000;
                if (!$subs->save()) {
                    throw new Exception($this->getReturnError($subs->getErrors()));
                }
            }

            $transaction->commit();
            $this->outputJson($this->returnMsg('success','提交成功'));
        }catch (Exception $e) {
            $transaction->rollback();
            $this->outputJson($this->returnMsg('failed',$e->getMessage()));
        }
    }

    //删除
    public function actionDel()
    {
        $paramConfig = array();
        $paramConfig[] = ParamValidator::jsonParam('id',array('title'=>'记录编号'));
        $params = $this->validateParams($paramConfig);

        //逻辑验证
        $model = Order::model()->findByAttributes(array('id'=>$params['id'],'hr_id'=>Yii::app()->user->id));
        $model or $this->outputJson($this->returnMsg('failed',"记录不存在"));
        $model->status == 0 && $this->outputJson($this->returnMsg('failed',"记录已删除"));
        $model->status == 3 && $this->outputJson($this->returnMsg('failed',"进行中的记录不能删除"));

        $transaction = Yii::app()->db->beginTransaction();
        try {
            $model->status = 0;
            $model->update_time = time();
            if (!$model->save()) {
                throw new Exception($this->getReturnError($model->getErrors()));
            }
            $transaction->commit();
            $this->outputJson($this->returnMsg('success','删除成功'));
        }catch (Exception $e) {
            $transaction->rollback();
            $this->outputJson($this->returnMsg('failed',$e->getMessage()));
        }
    }
}