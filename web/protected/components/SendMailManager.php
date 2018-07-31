<?php

/**
 * SendMailManager
 */
class SendMailManager extends BaseManager
{
    /**
     * 发送附件
     *
     * 参数传递：
     * $fromname：发件人姓名
     * $frommail：发件人邮箱
     * $to：收件人邮箱（多个以“;”隔开）
     * $subject：邮件标题
     * $html：邮件内容
     * $file：附件的绝对路径
     *
     * 实例：
     * SendMailManager::sendMailWithAttach("脚手架","admin@scaffolding.com","306539332@qq.com",
            "来自脚手架的简历","你好，附件是您的同事(马云)转发给您的宝贝，请查收!",
            'D:\Johnny\document\test.doc')
     *
     * 返回：
     * 发送成功返回true
     * 发送失败返回失败原因
     */
    public static function sendMailWithAttach($fromname,$frommail,$to,$subject,$html,$file)
    {
        $url = 'http://sendcloud.sohu.com/webapi/mail.send.json';

        $API_USER = 'api_user';
        $API_KEY = 'api_key';
        $param = array(
            'api_user' => $API_USER, # 使用api_user和api_key进行验证
            'api_key' => $API_KEY,
            'from' => $frommail, # 发信人，用正确邮件地址替代
            'fromname' => $fromname,
            'to' => $to, # 收件人地址，用正确邮件地址替代，多个地址用';'分隔
            'subject' => $subject,
            'html' => $html,
            'resp_email_id' => 'true'
        );

        $pathinfo = pathinfo($file);
        $handle = fopen($file, 'rb');
        $content = fread($handle, filesize($file));

        $eol = "\r\n";
        $data = '';

        $mime_boundary = md5(time());

        // 配置参数
        foreach ($param as $key => $value) {
            $data .= '--' . $mime_boundary . $eol;
            $data .= 'Content-Disposition: form-data; ';
            $data .= "name=" . $key . $eol . $eol;
            $data .= $value . $eol;
        }

        // 配置文件
        $data .= '--' . $mime_boundary . $eol;
        $data .= 'Content-Disposition: form-data; name="somefile"; filename="'.$pathinfo['basename'].'"' . $eol;
        $data .= 'Content-Type: text/plain' . $eol;
        $data .= 'Content-Transfer-Encoding: binary' . $eol . $eol;
        $data .= $content . $eol;
        $data .= "--" . $mime_boundary . "--" . $eol . $eol;

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data;boundary=' . $mime_boundary . $eol,
                'content' => $data
            ));
        $context = stream_context_create($options);
        $result = file_get_contents($url, FILE_TEXT, $context);
        fclose($handle);
        $res = CJSON::decode($result,true);
        if($res["message"]=="success"){
            return true;
        }else{
            $error = isset($res["errors"][0])?$res["errors"][0]:'发送邮件失败！';
            Yii::log("SendMailManager->sendMailWithAttach:".$error, "warning", "sendMail");
            return $error;
        }
    }


    /**
     * 发送模板邮件
     *
     * 触发类
     *
     * 参数传递：
     * $to：收件人邮箱（多个以“;”隔开）
     * $sub：模板参数
     * $tpl：模板名称
     *
     * 实例：
     *
     *
     * 返回：
     * 发送成功返回true
     * 发送失败返回失败原因
     */
    public static function sendMailWithTemplate($to, $sub, $tpl)
    {
        $psdata = array(
            'api_user'=>"api_user",
            'api_key'=>"api_key",
            'from'=>"admin@scaffolding.com",
            'fromname' => '脚手架',
            'substitution_vars'=>CJSON::encode(array(
                'to'=>$to,
                'sub'=>$sub,
            )),
            'template_invoke_name'=>$tpl,
        );

        $url = "http://sendcloud.sohu.com/webapi/mail.send_template.json";
        $data_string = http_build_query($psdata);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($data_string))
        );
        $output = curl_exec($ch);
        curl_close($ch);
        $res = CJSON::decode($output,true);
        if($res["message"]=="success"){
            return true;
        }else{
            $error = isset($res["errors"][0])?$res["errors"][0]:'发送邮件失败！';
            Yii::log("SendMailManager->sendMailWithTemplate:".$error, "warning", "sendMail");
            return $error;
        }
    }
}