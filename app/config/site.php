<?php
defined('PIC_PATH') or define('PIC_PATH', "/tb/webroot/i.tripb2b.com/"); // E:/webroot/i.tripb2b.com/i5.tripb2b.com/
if (!defined('BASEDIR'))
    define('BASEDIR', realpath(dirname(__FILE__) . '/../../..'));

return array(
    'cookie_salt' => '8e0c1y2', //cookie签名
    'log_dir' => BASEDIR . '/buyer/log', //日志文件目录777
    'service' => array(
        'order' => 'http://service.order.'.DOMAIN_SERVICE.'/', //订单
         'line' => 'http://service.'.DOMAIN_SERVICE.'/', // 线路

        //以下到时统一修改为tool
        'sms' => 'http://test.sms.service.etu6.org/', // 短信
        'message' => 'http://test.message.service.etu6.org/',  // 订单消息
        'push'  =>  'http://test.push.service.etu6.org/',  //推送消息
        'mail' => 'http://test.mail.service.etu6.org/', //邮件发送

        //java服务
        'passport' => 'http://test.passport.etu6.org/',  //passport地址
        'member' => 'http://test.uc.service.tripb2b.com/',  // 用户公司
        'site' => 'http://test.base.service.etu6.org/',  // 基础
        'promotion' => 'http://test.promotion.service.tripb2b.com/', // 营销活动
        'insurance' => 'http://test.insurance.service.tripb2b.com/', // 保险
        'pays' => 'http://test.pay.service.etu6.org/',  // C++支付
        'receive' => 'http://test.receive.service.tripb2b.com/', // 收客通java
        'ad' => 'http://test.ad.service.etu6.org/',  // 广告
    ),

    'params' => array(
        'host' => array('sitename' => 'XXX',
            'web' => 'http://' . $_SERVER['SERVER_NAME'] . '/',
            'images' => 'http://img.tb.yake.net/',
            'docx' => 'http://test.linedocx.etu6.org/',
        ),
        'upload' => PIC_PATH,
        'smtpserver' => array(
            'Host' => 'smtp.163.com', // SMTP server
            'SMTPDebug' => FALSE, //Sets SMTP class debugging on or off.
            'SMTPAuth' => TRUE, // Sets SMTP authentication. Utilizes the Username and Password variables.
            'SMTPSecure' => '', // sets the prefix to the servier,Options are "", "ssl" or "tls"
            'Port' => 25, // set the SMTP port for the mail server
            'Username' => 'm15397833753@163.com',
            'Password' => '1690lost',
            'SetFrom' => 'm15397833753@163.com',
            'SetFromName' => '馨·驰誉•馨·欢途',
            'IsHTML' => TRUE
        ),
    ),
);
