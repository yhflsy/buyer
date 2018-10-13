<?php
/**
 * 用户登录Passport
 */
class Controller_Passport extends Controller {

    public function action_index() {    
        $passportdomain = Kohana::$config->load('site.service.passport');
        include_once(DOCROOT . '../plugins/passport/CAS.php');
        phpCAS::setDebug(APPPATH . '/../cache/phpCAS_'.date("Y-m-d H").'.log');
        phpCAS::client(CAS_VERSION_2_0, parse_url($passportdomain, PHP_URL_HOST), 80, '');    //test.passport.etu6.org   test.passport.tripb2b.com

        phpCAS::setNoCasServerValidation();
        phpCAS::handleLogoutRequests();
        phpCAS::forceAuthentication();
   
        $refer = Filter::str('refer');
	if($refer){
	     $refer = str_replace("&cate=wap","",$_SERVER['REQUEST_URI']);
	     $refer = str_replace("/passport.html?refer=","",$refer);
	}
        $this->response->body(View::factory('passport/index', array(
            'sitedomain' => Kohana::$config->load('common.host'),   //三个网站域名
            'detail' => json_decode(base64_decode(phpCAS::getUser()), true),
            'serverhost' => $_SERVER['HTTP_HOST'],  //不只含首页
            'stop' => $_REQUEST['stop'],
            'passportdomain' => $passportdomain,
            'refer' => $refer.(Filter::str('timeIds')?'&timeIds='.Filter::str('timeIds'):''),
            'controller'=> $this,
			'cate' => $_REQUEST['cate']	//类型：web,wap,app
        )));
    }
    
    public function action_destoryAccount(){
        setcookie('PHPSESSID', NULL,NULL,"/",$_SERVER['SERVER_NAME']);
        @session_start();
        @session_destroy();
    }
    
    public function action_exit(){
        @session_start();
        $user = Session::instance()->get('TRIPB2BCOM_USER');
        if($user){
            Common::UnidState($user, 0);
        }
        $refer = Filter::str('refer');
        setcookie('PHPSESSID', NULL,NULL,"/",$_SERVER['SERVER_NAME']);
        @session_destroy();
        if($refer){
            $url = Kohana::$config->load('site.service.passport')."logout?service=http://{$refer}";
        }else{
           $url = Kohana::$config->load('site.service.passport')."logout?service=http://".$_SERVER['HTTP_HOST']; 
        } 
        header("Content-type: text/html; charset=utf-8");
        echo '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"><title>退出</title></head><body><div style="MARGIN-RIGHT: auto; MARGIN-LEFT: auto; margin-top: 100px; width: 200px;"><p style="text-align: center"><img src="/passportlogin.gif" style="MARGIN-RIGHT: auto; MARGIN-LEFT: auto;width:84px;height:81px;"><br />退出中，请稍候……</p></div><script>window.location.href=\'' . $url . '\'</script></body></html>';
        exit;           
    }
}