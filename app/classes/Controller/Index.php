<?php

class Controller_Index extends Controller_Base {

    public function before() {
        parent::before();
    }

    public function action_check(){
        exit(date('H:i:s'));
    }

    public function action_checkip(){
        header("Content-type: text/html; charset=utf-8");
        $ipcityname = Ip::getCityName();
        print_r($ipcityname);

        $siteid = $_COOKIE ['tripb2b_site_id'];
        print_r($siteid);
        die;
    }

    public function action_checksites(){
        $sites = $this->getSitesCache(1);
        print_r($sites);
        die;
    }

    // 首页
    public function action_index() {
        if($this->user){
            header("Location: lines.html?l=1");die;
        }
    }

    //验证用户名密码是否正确
    public function action_ajaxLogin() {
        $this->_eventAnalysis('10051');
        $user['username'] = Filter::str('username');
        $user['password'] = Filter::str('passwd');
        $user['rememberme'] = Filter::str('rememberme');
        if ($user['rememberme'] === 'true') {
            Cookie::set('rememberme', base64_encode(json_encode(array('username' => urldecode($user['username']), 'password' => urldecode($user['password']), 'rememberme' => 1))), time() + 86400);
        } else {
            Cookie::delete('rememberme');
        }
        $key = $user['username'] . ',' . $user['password'];
        $data = $this->jrequest($this->servicehost['member'] . 'api/member/logincheck/' . $key, self::GET)->get();
        echo json_encode($data);
        die();
    }

    //退出
    public function action_exit() {
        if($this->user){
            Cache::instance('default')->set(Common::Unid(), null);
        }
        
        if($this->user['companyinfo']['isseller'] == 5 || $this->user['companyinfo']['isseller'] == 6){//接送或地接退出处理
            header("Location: passport.exit.html");
            exit;   
        }else{
            $tempstr = Common::getExitKeyWords($this->user['companyinfo']['isseller'], $this->user['platformauth']);
            $url = urlencode("http://" . $this->sitedomain['index'][$this->platform] . "/index.html?sign=1&userkey={$tempstr}");
            $url = $this->servicehost['passport'] . "logout?service=" . $url;

            $this->session->set("TRIPB2BCOM_USER", FALSE);
            setcookie('PHPSESSID', 0);

            header("Content-type: text/html; charset=utf-8");
            echo '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"><title>退出</title></head><body><div style="MARGIN-RIGHT: auto; MARGIN-LEFT: auto; margin-top: 100px; width: 200px;"><p style="text-align: center"><img src="/passportlogin.gif" style="MARGIN-RIGHT: auto; MARGIN-LEFT: auto;width:84px;height:81px;"><br />退出中，请稍候……</p></div><script>window.location.href=\'' . $url . '\'</script></body></html>';
            exit;
        }
    }

    //提取站点首字母，更多
    public function action_getSites() {
        $siteInfo = $this->_getSite();
        if(!$siteInfo) {
            echo  "eval('var allCity =[]')";
            exit;
        }
        $newSite = [];
        foreach ($siteInfo as $key => $value) {
            if($value['titleShort'] && is_string($value['titleShort'])) {
                $newSite[] = '"'.implode('|', [
                        'siteName' => $value['siteName'],
                        'siteId' => $value['siteId'],
                        'titleShort' => strtolower($value['titleShort'])
                    ]).'"';
            }
        }
        echo  "eval('var allCity =[".implode(',', (array)$newSite)."]')";
        exit;
    }
    
    //判断当前平台，提示站点所在平台
    protected function _getSite() {
        if($this->user && is_array($this->user['siteinfo'])) {
            //接口获取卖家站点
            return $this->user['siteinfo'];
        } else {
            //获取当前平台所有站点
            $result = $this->getSitesCache();
            return $result ? array_merge($result) : $result; 
        }
    }
    

}
