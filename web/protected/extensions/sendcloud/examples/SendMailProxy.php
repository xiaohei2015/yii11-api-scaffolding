
<?php
Yii::import('application.extensions.sendcloud.lib.util.*');
Yii::import('application.extensions.sendcloud.lib.SendCloud');


class SendMailProxy{
    /*
     * 用于生成发送邮件的变量
     */
    private static function getVars($var,$count)
    {
        $data = [];
        foreach($var as $k=>$v){
            for($i=0;$i<$count;$i++){
                $data[$k][]=$v;
            }
        }
        return $data;
    }

    /**
     * 带附件邮件发送接口
     * @param
     *  $subs = [
            'info' => [
                'to' => ['306539332@qq.com','hudmharker@163.com'],
                'from' => 'admin@api.scaffolding.com',
                'subject' => '测试邮件2',
                'template' => 'template_id',
                'attach_path' => ['/data/template/att1.doc','/data/template/att2.docx'],
            ],
            'vars' => [
                '%name%' => 'xiaohei',
                '%content%' => 'this is content',
            ],
        ];
     */
    public static function sendMailWithAttach($subData){
        $sendcloud=new SendCloud(Yii::app()->params['sendCloud']['apiUser'], Yii::app()->params['sendCloud']['apiKey'],'v2');
        $mail=new Mail();
        $mail->setFrom($subData['info']['from']);
        $mail->setXsmtpApi(json_encode(array(
            'to'=>$subData['info']['to'],
            'sub'=>self::getVars($subData['vars'], count($subData['info']['to'])),
        )));
        $mail->setSubject($subData['info']['subject']);
        $mail->setRespEmailId(true);
        $templateContent=new TemplateContent();
        $templateContent->setTemplateInvokeName($subData['info']['template']);
        $mail->setTemplateContent($templateContent);

        foreach($subData['info']['attach_path'] as $v){
            $file = $v;
            $handle = fopen($file,'rb');
            $content = fread($handle,filesize($file));
            $filetype= Mimetypes::getInstance()->fromFilename($file);
            $attachment=new Attachment();
            $attachment->setType($filetype);
            $attachment->setContent($content);
            $attachment->setFilename(Helper::get_basename($file));
            $mail->addAttachment($attachment);
            fclose($handle);
        }

        return $sendcloud->sendTemplate($mail);
    }
}