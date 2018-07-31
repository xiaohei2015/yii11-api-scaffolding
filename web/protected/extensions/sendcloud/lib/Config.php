<?php
Class Config{
    public static $config = [
        'v1' => [
            'send' => '/webapi/mail.send.json',
            'sendTemplate' => '/webapi/mail.send_template.json',
        ],
        'v2' => [
            'send' => '/mail/send',
            'sendTemplate' => '/mail/sendtemplate',
        ],
    ];
}