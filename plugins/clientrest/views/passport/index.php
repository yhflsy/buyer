<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"><title>passport登录</title></head><body><div style="MARGIN-RIGHT: auto; MARGIN-LEFT: auto; margin-top: 100px; width: 200px;"><p style="text-align: center"><img src="/passportlogin.gif" style="MARGIN-RIGHT: auto; MARGIN-LEFT: auto;"><br />登录中，请稍候……</p></div></body></html>
<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if ($detail["code"] == 200) {
    
    $detail = $detail['result'];
    $p = getKey();
    if( ($detail['companyinfo']['isseller'] == 0 || $detail['companyinfo']['isseller'] == 1) && $p <> 'pay'){
        
        $detail['platformauth'] = Common::getPlatformAuth($detail['companyinfo']['registsource'], $detail['companyinfo']['isseller'], $cate);
        $platform = Common::getPlatformByDomain();   //获取当前域名的平台字符串        
    
        //平台限制
        if (empty($detail['platformauth'])) {
            Location('无法识别用户的买卖家身份和所属平台！请联系客服或技术人员！', $passportdomain . "logout?service=http://" . $sitedomain['index']['tripb2b']);
        }

        if (!$platform) {
            $platform = 'tripb2b';
        }

        if (!in_array($platform, $detail['platformauth'])) {
            Location('用户没有访问该平台的权限！' . $detail['memberinfo']['id'], $passportdomain . "logout?service=http://" . $serverhost);
        }

        //以下描述的情况不做处理
        //域名的限制，当前找不到场景必须来进行限制
        //$serverhost是买家域名，用户却是卖家，或者相反【需(会)在买卖家用户中心处理】
        //站点限制
        if (($detail['companyinfo']['companytype'] != 1 || $detail['companyinfo']['isseller'] != 1) && empty($detail['siteinfo'])) {
            Location('该用户没有可访问的站点，请联系客服分配！' . $detail['memberinfo']['id'], $passportdomain . "logout?service=http://" . $serverhost);
        }

        //权限限制
        if (empty($detail['resources']) || !is_array($detail['resources'])) {
            Location('该用户没有被分配权限！请联系管理员分配！' . $detail['memberinfo']['id'], $passportdomain . "logout?service=http://" . $serverhost);
        }
        
       Common::dealWithCasUserInfo($detail);
    }

    Session::instance()->set("TRIPB2BCOM_USER", $detail);
    Common::UnidState($detail, 1);
    Cache::instance('default')->set(Common::Unid(), time(), 86400); 
    if($detail['companyinfo']['isseller'] == 1 || strstr($refer, 'sign='))
    {
        $refer = '';
    }
    $url = $refer ? $refer : getUrl($detail, $platform, $controller);
    header("Content-type: text/html; charset=utf-8");
    echo "<!DOCTYPE html><head></head><body onload=\"window.location.href='" . $url . "'\"></body></html>";
    exit;
} else {
    Location('Passport注册失败！错误编号：' . $detail["code"], $passportdomain . "logout?service=http://" . $serverhost);
}

function getUrl($detail, $platform = '', $controller) {
    $p = getKey();
    $domain = $_SERVER['HTTP_HOST'];
    $confHost = Kohana::$config->load('common.host');
    switch ($p) {
        case 'flagshipweb' :
            $url = $domain . '/' . $controller->request->param('scid');
            break;
        
        case 'index':
        case 'buyer':
        case 'seller':
            if ($detail['companyinfo']['isseller'] == 6) { //地接
                $url = $confHost['ground'][0];
            }elseif ($detail['companyinfo']['isseller'] == 5) { //接送
                $url = $confHost['take'][0];
            }elseif ($detail['companyinfo']['isseller'] == 1) { //供应商
                $url = $detail['companyinfo']['companytype'] != 1 ? $detail['companyinfo']['companytype'] != 10 ? $confHost['seller'][$platform] : $confHost['centuryship']['tripb2b'] : $confHost['flagship']['tripb2b'];
            } elseif($detail['companyinfo']['isseller'] == 0) { //组团社
                $url = $detail['companyinfo']['companytype'] == 1 ? $confHost['buyer'][$platform] . '/order.largetour.html' : $confHost['index'][$platform] . '/';
            }
            break;

        default:
            $url = $domain;
            break;
    }

    return 'http://' . $url;
}

function getKey() {
    $confHost = Kohana::$config->load('common.host');
    foreach ($confHost as $k => $v) {
        if(in_array($_SERVER['HTTP_HOST'], $v)){
            return $k;
        }
    }
}

function Location($message, $url) {
    @session_start();
    @session_destroy();
    header("Content-type: text/html; charset=utf-8");
    echo "<script type=\"text/javascript\">alert('" . $message . "');</script>";
    echo "<script type=\"text/javascript\">location.href='" . $url . "'</script>";
    //echo View::factory('dialog/msg', array('status' => 2, 'message' => $message, 'location' => $url ));    
    exit;
}
