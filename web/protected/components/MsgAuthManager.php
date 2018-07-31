<?php
/**
 * MsgAuthManager
 */
class MsgAuthManager extends BaseManager
{
    /**
     * 发送消息
     */
    public static function sendMsg($phone, $product='脚手架', $sign='身份验证', $template_id=null)
    {
        //权限验证
        $criteria = new CDbCriteria();
        $criteria->condition = "phone=".$phone." and send_time>=".((time()-1*60)*1000);
        $msgLast = SysMsgAuthLog::model()->count($criteria);
        if($msgLast>=1){
            return '您提交的太快了，请稍后再试!';
        }

        $criteria = new CDbCriteria();
        $criteria->condition = "phone=".$phone." and send_time>=".((time()-1*60*60)*1000);
        $msgLast = SysMsgAuthLog::model()->count($criteria);
        if($msgLast>=5){
            return '您提交的太快了，请稍后再试!';
        }

        $criteria = new CDbCriteria();
        $criteria->condition = "phone=".$phone." and send_time>=".((time()-1*24*60*60)*1000);
        $msgLast = SysMsgAuthLog::model()->count($criteria);
        if($msgLast>=10){
            return '您提交的太快了，请稍后再试!';
        }

        $criteria = new CDbCriteria();
        $criteria->condition = "ip='".self::userIp()."' and send_time>=".((time()-1*24*60*60)*1000);
        $msgIPCount = SysMsgAuthLog::model()->count($criteria);
        if($msgIPCount>=50){
            return '您提交的太快了，请稍后再试!';
        }

        $code = strval(rand(100000,999999));
        //发送短信
        $template_id = $template_id?:'SMS_13005615';
        $content = json_encode(array('code'=>$code,'product'=>$product));
        $result = Yii::app()->SmsService->sendSMS($phone,$content,$sign,$template_id);
        if(isset($result->result->err_code) && $result->result->err_code == 0){
            SysMsgAuthLog::model()->updateAll(array('status'=>1),'phone=:phone and  status=0',array(':phone'=>$phone));
            //记录验证码至数据库
            $model = new SysMsgAuthLog();
            $model->phone = $phone;
            $model->send_time = time() * 1000;
            $model->code = $code;
            $model->ip = self::userIp();
            $model->status = 0;
            @$model->save();
            return true;
        }else{
            if(isset($result->sub_msg)){
                $error = $result->sub_msg;
            }elseif(isset($result->msg)){
                $error = $result->msg;
            }elseif(is_string($result)){
                $error = $result;
            }else{
                $error = serialize($result);
            }
            Yii::log("MsgAuthManager->sendMsg:".$error, "warning", "sendmsg");
            return $error;
        }
    }

    /**
     * 验证码验证
     */
    public static function verifyCode($phone, $code, $is_destroy=false)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = "status=0 and send_time>=".(time()-Yii::app()->params["MsgTimeOut"])*1000;
        $criteria->compare('phone',$phone);
        $criteria->order = "send_time DESC";
        $model_msg = SysMsgAuthLog::model()->find($criteria);
        if(!isset($model_msg)){
            return '校验码已经失效, 请重新获取校验码';
        }
        if($model_msg->failed_count >= 3){
            return '输入错误的次数超过3次，请重新获取短信';
        }
        if($model_msg->code!=$code){
            SysMsgAuthLog::model()->updateAll(array('failed_count'=>$model_msg->failed_count+1),'id=:id',array(':id'=>$model_msg->id));
            if($model_msg->failed_count >= 2){
                return '输入错误的次数超过3次，请重新获取短信';
            }else{
                return '短信验证码有误';
            }
        }else{
            if($is_destroy){
                SysMsgAuthLog::model()->updateAll(array('status'=>1),'phone=:phone and  status=0',array(':phone'=>$phone));
            }
            return true;
        }
    }
}