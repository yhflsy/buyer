<?php


class Controller_Registeredbase extends Controller_Base {

    public function before() {
        parent::before();
        $this->themes = 'web.tripb2b.v3.1';
    }
    //验证码
    public function action_getValidateCode(){
        session_start();
        $_SESSION["validatecode"] = Common::getValidateCode();
    }
    //图形验证码通过并发送短信
    public function action_sendvcode(){
        if(strcasecmp(strtolower($_SESSION["validatecode"]), strtolower(Filter::str('validatecode'))) != 0){
            echo json_encode(['state' => 2,'info'=>'图形验证码错误']);
            exit();
        }
        $mobile = Filter::str('mobile');
        if(!Common::verifyPhone($mobile)){
            echo json_encode(['state' => 3,'info'=>'手机号码格式错误']);
            exit();
        }
        $sendVCode = $this->jrequest('/api/uc/account/sendvcode/'.$mobile, self::GET, '')->get();
        $verifyCode = explode("_",$sendVCode['result']);
        if ($sendVCode && $sendVCode['code'] == 200) {
            echo json_encode(['state' => 1,'verifycode'=>$verifyCode[1]]);
        } else {
            echo json_encode(['state' => 0, 'info'=>'验证码发送失败']);
        }
        exit();
    }
    //检查手机是否可用
    public function action_ajaxCheckMobile() {
        $tel = Filter::str('tels');
        $checkmobile = $this->jrequest("api/uc/account/check/{$tel}", self::GET)->get();
        if ($checkmobile['code'] == 200) {
            if ($checkmobile['result'] == 'true') {
                echo json_encode(['status' => 'y', 'info' => '手机号码可以注册！']);
            } else {
                echo json_encode(['status' => 'n', 'info' => '手机号码注册过了！']);
            }
        } else {
            echo json_encode(['status' => 'n', 'info' => '检测失败！']);
        }
        exit;
    }
    
    //检测用户名
    public function action_checkUsername($params = ''){
        $username = $this->request->is_ajax() ? $_REQUEST['param'] : $params;
        if(!Valid::regex($username, "/^[a-zA-Z0-9]\w{3,19}$/")){
            echo json_encode(['status' => 'n', 'info' => '用户名不合法！']);exit;
        }
        $username = urlencode($username);
        $res = $this->jrequest("api/member/check/{$username}", self::GET)->get();
        if($res['code']==200){
            if($res['result']){
                echo json_encode(['status' => 'n', 'info' => '用户名已存在！']);
            }  else {
                echo json_encode(['status' => 'y', 'info' => '可以注册！']);
            }
        }  else {
            echo json_encode(['status' => 'n', 'info' => '检测失败！']);
        }
        exit;
    }
    
    //检测公司信息
    public function action_checkCompanyname($params = '') {
        $companyname = $this->request->is_ajax() ? $_REQUEST['param'] : $params;
        if (mb_strlen($companyname, 'UTF8') < 4) {
            echo json_encode(['status' => 'n', 'info' => '不合法！']);exit;
        }
        $companyname = urlencode($companyname);
        $type = 0;
        $res = $this->jrequest("api/company/check/{$companyname}", self::GET)->get();
        $retcode = $res['code'] == 200 ? ($res['result'] == array() ? ['status' => 'y', 'info' => '可以注册！'] : ['status' => 'n', 'info' => '公司已存在！']) : ['status' => 'n', 'info' => '检测失败！'];
        echo json_encode($retcode);exit;
    }
    
}

