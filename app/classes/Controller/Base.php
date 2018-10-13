<?php

/**
 * 以下几个_REQUEST有的，是base中专用的
 * sign 标识：用户是退出后调到首页的；可使用此值来清除首页的session ，且此变量是“首页进行iframe退出”的2个条件之一（另个条件:user不存在）
 * keywords 标识：用户拥有的平台以及自己买卖家身份
 * plattime 标识：埋在每个页面的首页链接上，当用户登录的时候页面上的这个参数有值
 */
class Controller_Base extends Controller {

    protected $directory;
    protected $controller;
    protected $action;
    protected $rest;
    public $cache;  //cache 缓存
    public $cacheopen; //缓存设置开关
    public $skinPath;   // string 模版路径
    protected $template;
    protected $themes;
    public $templateName;
    public $view = array();
    public $page;
    protected $search;  //列表搜索条件
    public $siteid; //当前站点
    public $allSites;   //系统全部站点值
    public $user;   //当前用户的信息
    protected $sitedomain;   //定义的9个域名的数组 对应配置文件 plugins.common.host
    protected $serverhost;   //当前访问的域名
    protected $servicehost; //调用其他服务时的地址数组
    protected $websitevalue;
    public $platform;   //当前的平台 平台标识字符串
    public $roles; //所有的
    public $roleid; //当前模块的权限id
    public $ucurl;  //用户的用户中心地址
    public $css;
    const cacheTime = 7200;
    const GET = Request::GET;
    const ADD = Request::POST;
    const UPDATE = Request::PUT;
    const DELETE = Request::DELETE;

    public function before() {  // before 中不要view数据到页面
        parent::before();

        $this->_init();
        $this->_checkIsMobile();
        $this->_initUserInfo();

        $this->_initPage();
        $this->_initSearch();
        $this->_initRest();
        $this->_setLink();
        $this->_setSiteid();
        $this->_getRoles();
    }

    public function after() {
        $clearCache = Filter::str('cache');
        if ($clearCache == 'clear') {
            $this->_clearCache();
        }

        $this->_setupPage();
        $this->_setupSearch();
        $this->_setupView();
        $this->_setupProfiling();
    }

    private function _initUserInfo(){
       $this->_initSC();   //初始化session和cache
       Common::ValidRemoteUser();
       $this->_checkAccess();  //检测是否登录
    }

    private function _checkAccess() {
        if($this->_noNeedToCheckAccess()){
            return ;
        }

        //完成对$this->user的赋值，并完成对无效访问的拦截
        if(in_array($this->serverhost, $this->sitedomain['index'])){    //首页不走passport，只走本地session，所以本地首页域名下的session用来标识用户是否登录
            $this->user = $this->session->get('TRIPB2BCOM_USER');   // 该行登录的首页
            //--start 以下几行是为了防止由A平台进入B平台首页
            if($_REQUEST['plattime']){
                if($_SESSION['phpCAS']['user']){    //CAS有效
                    $this->user = $this->session->get('TRIPB2BCOM_USER');
                    if(empty($this->user)){ //session无效了
                        Common::Location('http://'.$this->serverhost.'/passport.html?refer=http://'.$this->serverhost.$_SERVER['REQUEST_URI']);
                    }   // 如果有效则直接使用 session
                }else{  //CAS无效
                    Common::Location('http://'.$this->serverhost.'/passport.html?refer=http://'.$this->serverhost.$_SERVER['REQUEST_URI']);
                }
            }//--end
        }elseif(in_array($this->serverhost, $this->sitedomain['buyer'])){
            if($_SESSION['phpCAS']['user']){    //CAS有效
                $this->user = $this->session->get('TRIPB2BCOM_USER');
                if(empty($this->user)){ //session无效了
                    Common::Location('http://'.$this->serverhost.'/passport.html');
                }   // 如果有效则直接使用 session
            }else{  //CAS无效
                Common::Location('http://'.$this->serverhost.'/passport.html?refer=http://'.$this->serverhost.$_SERVER['REQUEST_URI']);
            }
        }else{
            $platform = Common::getPlatformByDomainWords($this->serverhost);
            Common::Location("http://" . $this->sitedomain['index'][$platform]);
        }

        //对$this->user进行加工
        if($this->user){
            if($this->user['companyinfo']['isseller']){ //以下3行兼容卖家的 user 信息
                $this->user['id'] = $this->user['memberinfo']['id'];
                $this->user['companyid'] = $this->user['memberinfo']['companyid'];
                $this->user['isseller'] = 1;
                if(in_array($this->serverhost, $this->sitedomain['buyer'])){
                    $this->showMessage('您不能访问组团社功能', "http://" . $this->sitedomain['index'][$this->platform]);
                }
            }
            if(!$this->user['companyinfo']['siteidlist'] && !empty($this->user['siteinfo'])) {  //设置买卖家拥有的站点列表和默认站点
                if ($this->websitevalue[$this->platform] == 2) {
                    foreach ($this->user['siteinfo'] as $vaule) {
                        if (strstr($vaule['website'], "2")) {
                            $siteinfo[$vaule['siteId']] = $vaule;
                            $this->user['siteinfo']=$siteinfo;
                        }
                    }
                }
                if ($this->websitevalue[$this->platform] == 4) {
                    foreach ($this->user['siteinfo'] as $vaule) {
                        if (strstr($vaule['website'], "4")) {
                            $siteinfo[$vaule['siteId']] = $vaule;
                            $this->user['siteinfo']=$siteinfo;
                        }
                    }
                }
                $this->user['companyinfo']['siteidlist'] = Common::arr2ToArr($this->user['siteinfo'], 'siteId'); //含买家的默认站点
                ($this->user['companyinfo']['isseller'] == 0) && $this->user['companyinfo']['defaultsite'] = $this->_getDefaultSiteid($this->user['siteinfo']);   //买家才需要设置
            }
            $this->_relogin();//卖家自动登录
        }

        if (APP_DEBUG === true && Filter::int('_printuser')) {
            echo '<pre>';
            print_r($this->user);
            exit;
        }        
    }

    // 初始化变量
    private function _init() {
        $this->directory = strtolower($this->request->directory());
        $this->controller = strtolower($this->request->controller());
        $this->action = strtolower($this->request->action());

        $this->servicehost = Kohana::$config->load('site.service');
        $this->sitedomain = Kohana::$config->load('platform.host');
        $this->websitevalue = Kohana::$config->load('platform.websitevalue');
        $this->serverhost = $_SERVER['HTTP_HOST'];  // 6种值：3个为首页 3个为买家用户中心
        $this->platform = Common::getPlatformByDomain();
        $this->siteid = 0;
    }

    private function _initSC(){
        $this->cacheopen = true;
        $this->cache = Cache::instance('default');
        session_start();
        $this->session = Session::instance();

        if (Filter::int('sign')) {   //在本文件中传递的这两个参数,防止跳转到页面时页面session还存在
            $this->session->set("TRIPB2BCOM_USER", FALSE);
            $_SESSION['phpCAS']['user'] = array();
        }
    }

    // 清除缓存
    private function _clearCache() {
        if ($this->cacheopen) {
            $this->cache->delete_all();
            $this->showMessage('缓存清除成功!', str_replace('cache=clear', '', $_SERVER['REQUEST_URI']));
        }
    }

    //检测是否是mobile请求，若是则调到 wap
    //此处代码上传到正式时要注意
    private function _checkIsMobile() {
        if($this->controller == 'print' && ($this->action == 'guest' || $this->action == 'index'|| $this->action == 'single' || $this->action == 'confirm')){
            return;
        }

        if ($this->action != 'check') {
            if ($this->_checkmobile()) {
                if ($this->controller == 'details') {
                    Common::Location("http://" . $this->sitedomain['wei'] . '/buyer.line.detail.html?id=' . $_GET['dateid']. '&fx=1&takeid=' .$_GET['r'] . '&mid=' .$_GET['mid']);
                }
                Common::Location("http://" . $this->sitedomain['wei']);
            }
        }
    }

    //不需要经过权限验证
    private function _noNeedToCheckAccess() {
        if ($this->controller == 'print' || $this->action == 'upload' || $this->action == 'datatask' || $this->action == 'setplatfrom' || $this->action == 'payreturn' || $this->action == 'autologin' || ($this->controller=='index' && $this->action=='check' || $this->controller == 'hotel' || $this->controller == 'gprint' || $this->controller=='gprints')) {
            return true;
        }
        if ($this->controller == 'error') {   //不要将此段与上一段合并
            return true;
        }
    }

    private function _setSiteid() {
        $siteidcookiename = 'tripb2b_site_id';
        $siteid = $_COOKIE [$siteidcookiename];
        $usersiteid = $_COOKIE [$siteidcookiename.'login'];
        $_GET['sid'] = $_GET['sid']? :$_GET['siteid'];
        if($this->user){
            $this->siteid = $this->user['companyinfo']['defaultsite'] ? $this->user['companyinfo']['defaultsite']['siteId'] : array_rand($this->user['siteinfo']);
            $usersiteid = $_COOKIE [$siteidcookiename.'login'];
            $usersiteid && $this->siteid = $usersiteid;
            if (isset($_GET['sid']) && $_GET['sid'] != "") {
                if (!in_array($_GET['sid'], $this->user['companyinfo']['siteidlist'])) {   //买卖家只能访问自己被分配的站点
                    $this->showMessage('该站不可访问，若需访问请联系管理员开通', 'http://'.$this->serverhost.'?sid='.$this->siteid );
                } else {
                    $this->siteid = (int) $_GET['sid'];
                    setcookie($siteidcookiename.'login', $this->siteid, NULL, "/", $_SERVER['SERVER_NAME']);
                }
            }
            
        }else{
	    $this->siteid = $this->_getSiteidFromIp();
	    $siteid && $this->siteid = $siteid;
            if (isset($_GET['sid']) && $_GET['sid'] != "") {
                $this->siteid = (int) $_GET['sid'];
                setcookie($siteidcookiename, $this->siteid, time() + 3600, "/", $_SERVER['SERVER_NAME']);
            }
            $usersiteid &&  setcookie($siteidcookiename.'login', '', time() - 3600, "/", $_SERVER['SERVER_NAME']);
        }
    }

    // 初始化search
    private function _initSearch() {
        $this->search = array();
    }

    // 初始化分页
    private function _initPage() {
        $page = new Page;
//        $_SERVER['REQUEST_URI'] = str_replace('sign=1','',$_SERVER['REQUEST_URI']);
        $page->uri = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $page->query = $_REQUEST;
        $page->current(filter_input($this->request->method() == Request::POST ? INPUT_POST : INPUT_GET, 'p', FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1, 'max_range' => Page::$maxPage))));
        $page->size(filter_input(INPUT_GET, 'ps', FILTER_VALIDATE_INT, array('options' => array('default' => Page::$defaultSize, 'min_range' => 1, 'max_range' => Page::$maxSize))));
        $this->page = $page;
    }

    // 设置视图
    private function _setupView() {
        $this->_setupTemplate();
        $this->_setupTemplateName();
        $templateDir = $this->template->getTemplateDir();
        $templateFile = (is_array($templateDir) ? $templateDir[0] : $templateDir) . $this->templateName;

        if (Kohana::$profiling === TRUE) {
            $smartybench = Profiler::start('加载视图文件', $templateFile);
        }
        if (!is_file($templateFile)) {
            if (APP_DEBUG === true) {
                echo sprintf("<h1>Missing file: %s</h1>", $templateFile);
            } elseif (Request::$current->requested_with() != 'xmlhttprequest') {
                echo View::factory('500');
            }
        } else {
            $this->template->display($this->templateName);
        }

        if (isset($smartybench)) {
            Profiler::stop($smartybench);
        }
    }

    // 初始化模板
    private function _setupTemplate() {
        $cssHost = Kohana::$config->load('platform.cssHost');
        $commonUrl = 'common';
        $this->themes = 'web.v1';
        $this->css = 'buyer/web.v1';

        $this->skinPath = "/skin/{$this->themes}";
        $this->view['skinUrl'] = $this->skinPath . '/';
        $this->view['skinPath'] = $cssHost.'/'.$this->css.'/';
        $this->view['staticUrl'] = $cssHost.'/'.$this->css.'/';
        //$this->view['skinPath'] = $this->skinPath . '/';
        $this->view['commonUrl'] = $cssHost.'/'.$commonUrl.'/';
        $this->view['host'] = 'http://'.$_SERVER['HTTP_HOST'].'/';
        $this->view['directory'] = $this->directory;
        $this->view['controller'] = $this->controller;
        $this->view['action'] = $this->action;
        $this->view['nowtime'] = time();
        $this->view['linesiteid']=$this->siteid;
        $this->view['imghost'] = Kohana::$config->load('site.params.host.images');
        $sessionsiteid = $this->siteid;
        $query = empty($sessionsiteid) ? "" : "/index.html?siteid={$sessionsiteid}";
        $this->view['shop'] = $this->sitedomain['shop'][$this->platform].$query;
        $this->view['shopurl'] = $this->sitedomain['shop'][$this->platform];
        $this->view['defaultlogo'] = Kohana::$config->load('common.defaultlogo');
        $this->view['user'] = $this->user;
        $this->view['rememberme'] = json_decode(base64_decode(Cookie::get('rememberme')), TRUE);

        $this->view['platform'] = $this->platform;  //平台字符串标识
        $this->view['websitevalue'] = $this->websitevalue[$this->platform];
        $this->view['webhost'] = $this->sitedomain;    //平台的9个域名
        $this->view['weburl'] = 'http://' . $this->serverhost . '/';    //当前域名
        $this->view['buyerdomain'] = 'http://' . $this->sitedomain['buyer'][$this->platform] . '/';   //买家域名
        $this->view['passportlogin'] = $this->servicehost['passport'];    //passport注册地址
        $this->view['callback'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if($query){
            if(preg_match('/(?=.+)=./i', $query)){
                $this->view['callback'] = "http://" . $this->serverhost . $_SERVER['REQUEST_URI'];
            }
        }
        $this->view['serverip'] = $_SERVER ['LOCAL_ADDR'];
        $this->view['siteid'] = $this->siteid;

        $this->view['kwtp'] = Filter::int('kwtp', 1); //搜素的类型
        $this->_setUcUrl();
        $this->view['uc'] = $this->ucurl;  //点击进入用户中心
        if($this->user){
            $this->view['plattime'] = $this->view['nowtime'];   //当前时间，用户标记登录
            if($this->user['companyinfo']['companytype']=='2'){
                $fpinfo = $this->jrequest('api/invoice/queryCompanyInvoiceAccount/' . "{$this->user['companyinfo']['id']}", self::GET)->get();
                if($fpinfo['code'] == 200 ){
                    if( $fpinfo['result']['status'] == 1 || $fpinfo['result']['status'] == 0){
                        $isinvoice = 1;
                    }
                }
                $this->view['isinvoice'] = $isinvoice;
            }
        }else{
            $userkey = Filter::int('userkey');
            if($_REQUEST['sign'] && $userkey){
                $this->view['basedomain'] = Common::getSitedomainByKeyWords($userkey, $this->sitedomain);
            }
        }
        $this->view['roles'] = $this->roles;
        //新首页
        $this->view['history'] = $this->_getSearchCache();
        $this->view['platformName'] = $this->websitevalue[$this->platform] == 2?'馨·欢途':'馨·驰誉';

        $this->view['sites'] = $this->user ? $this->user['siteinfo'] : $this->getSitesCache();  //买卖家只能访问自己被分配的站点

        if (in_array($this->serverhost, $this->sitedomain['buyer'])) {
                if ($this->roles['roleids'] && $this->roleid) {
                if (!in_array($this->roleid, json_decode($this->roles['roleids']))) {
                    $this->showMessage('没有权限，请与管理员联系', 'http://' . $this->sitedomain['buyer'][$this->platform] . '/');
                }
            }
        }
        
        if ($this->serverhost != $this->sitedomain['buyer'] && $this->controller != 'home') {
            $this->_getdests();//馨·驰誉的首页类目
        }


        $this->view['docx'] = Kohana::$config->load('site.params.host.docx');

        $this->template = Template::getInstance($this->themes);
        $this->template->assign($this->view);
    }

    // 初始化模板文件名
    private function _setupTemplateName() {
        if (isset($this->templateName)) {
            return;
        }

        if ($this->action == 'index' && $this->controller == 'index') {
            $this->templateName = 'index.html';
        } else if ($this->action == 'index') {
            $this->templateName = "{$this->controller}.html";
        } else {
            $this->templateName = "{$this->controller}.{$this->action}.html";
        }
        $directory = ['web','insurance','contract','ground','mail','theme','visa','econtract','credit','admin'];
        if(in_array($this->directory, $directory)){
            $this->templateName = $this->directory . '/' . $this->templateName;
        }
    }

    // 设置分页
    private function _setupPage() {
        if (!isset($this->page)) {
            return;
        }

        $this->page->build();
        $this->view['page'] = $this->page;
    }

    // 设置search
    private function _setupSearch() {
        $this->view['search'] = json_encode($this->search);
    }

    // 调试信息
    private function _setupProfiling() {
        if (APP_DEBUG !== false) {
            echo View::factory('profiler/stats');
        } else {//说明：生产环境调试开关
            $debug = Filter::str('debug');

            if ($debug === 'open') {
                echo View::factory('profiler/stats');
            }
        }
    }

    //生成url
    private function _setLink() {
        if ($this->action == 'index' || $this->action == 'list' || $this->action == 'details' || $this->action== 'localtour') {
            class_exists('ALink') && ALink::instance();
        }
    }

    //请求初始化
    private function _initRest() {
        $this->rest = Restful::instance();
        if (is_array($this->servicehost)) {
            $server = isset($this->servicehost[$this->directory]) ?
                    $this->servicehost[$this->directory] : ( isset($this->servicehost['default']) ? $this->servicehost['default'] : array_values($this->servicehost)[0] );
            $this->rest->setServer($server);
        } else {
            $this->rest->setServer((string) $this->servicehost);
        }
    }

    public function request($url, $method, $data = array(), $host = 'line', $headerdata = array()) {
        if ($host) {
            $host = $this->servicehost[$host];
            $host && $this->rest->setServer($host);
        }

        return $this->rest->request($url, $method, $data, $headerdata);
    }

    public function jrequest($url, $method, $data = array(), $host = 'member', $headerdata = array()) {
        $oldserver = $this->rest->getServer();
        $this->rest->setRealMethod(true);
        $data = $this->request($url, $method, $data, $host, $headerdata);
        $this->rest->setRealMethod(false);
        $oldserver && Restful::instance()->setServer($oldserver);
        return $data;
    }

    /**
     * 先提示再跳转
     * @param type $message
     * @param type $url
     * @param type $alert 只想弹出，不想跳转时置为TRUE;当使用TRUE时需考虑是否有必要在语句后使用die();
     */
    protected function showMessage($message, $url = '', $alert = FALSE) {
        $status = 1;
        if(strpos($message, '成功') !== false){
            $status = 0;
        }elseif(strpos($message, '失败') !== false){
            $status = 2;
        }        
        $this->response->body(View::factory('msg', array('status' => $status, 'message' => $message, 'location' => $url ? $url : $this->request->uri())));
        if(!$alert){
            echo $this->response->body();
            exit;
        }
    }

    /**
     * 提示不跳转
     * @param type $message
     */
    protected function showMessageOnly($message) {
        $this->showMessage($message, "javascript:windows.close();", true);
    }

    /**
     * 错误提示并跳转
     * @param type $code 错误码
     * @param type $message 错误信息
     * @param type $url 跳转页面
     * @param type $alert 只想弹出，不想跳转时置为TRUE;当使用TRUE时需考虑是否有必要在语句后使用die();
     */
    protected function showErrMessage($code, $message, $url = '', $alert = FALSE) {
        $this->showMessage(sprintf('%s \n\n错误码:%s', $message, $code), $url, $alert);
    }

    /**
     * 说明：错误信息写入header
     * @param type $obj 所有返回值
     */
    protected function setErrToHeader($obj) {
        ob_end_clean();
        header('Content-Type:text/html;charset=utf-8');
        header('Code:' . $obj['code']);
    }

    /**
     * 获取三网首页站点缓存信息
     * @param string $platform platform字符串
     * @param int $refresh 是否强制更新缓存
     * @return type
     */
    protected function getSitesCache($refresh = 0) {
        $sites = $this->cache->get('TRIPB2BCOM_SITES');   //数组，key值为platform
        $clearCache = Filter::str('cache');
        if (!$sites[$this->platform] || $refresh || $clearCache) {
            $data = $this->jrequest($this->servicehost['site'] . 'api/base/sites/platform/' . $this->platform, self::GET)->get();
            if ($data['code'] == 200 && $data['result']['siteList']) {
                foreach ($data['result']['siteList'] as $value) {
                    $sites[$this->platform][$value['siteId']] = $value;
                }
                $this->cache->set('TRIPB2BCOM_SITES', $sites, 3600 * 24 * 24); //设置到缓存
            }
        }
        return $sites[$this->platform];
    }

    /**
     * 从登录后的返回信息中获取公司默认站点
     * @param type $siteInfo
     * @return array
     */
    private function _getDefaultSiteid($siteInfo) {
        $siteid = array();
        if (empty($siteInfo) || !is_array($siteInfo)) {
            return $siteid;
        }

        foreach ($siteInfo as $value) {
            $value['isdefault'] && $default = explode(',', $value['defaultWebsite']);
            if ($value['isdefault'] && in_array($this->websitevalue[$this->platform], $default)) {
                $siteid = $value;
                break;
            }
        }
        return $siteid;
    }

    // 获取Msg
    private function _getNewMsg() {

        //是否为卖家，ID是否存在
        if (!isset($this->user['companyinfo']['id'])) {
            return;
        }

        $msg = $this->jrequest('stat', self::GET, array('companyid' => $this->user['companyinfo']['id']), 'message')->result();
        $msg = $msg[$this->user['companyinfo']['id']];
        if ($this->user['companyinfo']['isseller'] == 1) {
            $msg = array(
                '0' => array('count' => ($msg[0] ? $msg[0] : 0), 'title' => '新订单'),
                '2' => array('count' => ($msg[2] ? $msg[2] : 0), 'title' => '取消订单'),
                '8' => array('count' => ($msg[8] ? $msg[8] : 0), 'title' => '支付订单'),
                '16' => array('count' => ($msg[16] ? $msg[16] : 0), 'title' => '退款订单'),
            );
        } else {
            $msg = array(
                '0' => array('count' => ($msg[0] ? $msg[0] : 0), 'title' => '新订单'),
                '1' => array('count' => ($msg[1] ? $msg[1] : 0), 'title' => '确认订单'),
                '2' => array('count' => ($msg[2] ? $msg[2] : 0), 'title' => '取消订单'),
                '9' => array('count' => ($msg[9] ? $msg[9] : 0), 'title' => 'b2c新订单'),
            );
        }

        if ($msg) {
            foreach ($msg as $value) {
                $aResult['count']+=$value['count'];
            }
        }
        $aResult['msg'] = $this->user['companyinfo']['isseller'] < 5 ? $msg : [];
        return $aResult;
    }
    //逾期
    private function _getOrderLate(){
        //是否为卖家，ID是否存在
        if (!isset($this->user['companyinfo']['id'])) {
            return;
        }
        //订单融逾期订单数
        $orderLate = $this->request('order/message', self::GET, array('companyid' => $this->user['companyinfo']['id']), 'order')->result();
        return $orderLate;
    }

    
    

    //获取照片方法 传入一个或多个照片ID
    protected function getphotos($ids) {
        $getphotos = array(
            'type' => 'buyer', //服务类型
            'photoids' => $ids, //照片ID
        );
        $photo = $this->rest->request('line/photo', self::GET, $getphotos)->result();
        if ($photo) {
            $photos = array();
            foreach ($photo['list'] as $value) {
                $photos[$value['id']] = $value['photo'];
            }
            return $photos;
        }
        return;
    }

    //所有的权限id
    private function _getRoles() {
        $this->roles = array(
            'menu' => $this->user['authorization']['menus'],
            'roleids' => json_encode($this->user['authorization']['values'])
        );
    }

    //当前权限判断
    protected function _check($roleid) {
        $this->roleid = $roleid;
    }

    //发送邮件
    protected function _sendMail(array $to, $title, $content) {
        $params = array(
            'to' => $to,
            'title' => $title,
            'content' => $content,
        );
        $result = $this->jrequest('send', self::ADD, $params, 'mail')->get();
        return $result['code'] == 200 ? 1 : 0;
    }

       //馨·驰誉首页类目
    private function _getdests($refresh=0) {
        try {
            $logkey = sprintf("php:home:catalogs:%s", md5(sprintf("%s,%s", $this->siteid, $this->platform)));
            $c = $this->redisCache();
            $destlist = $c->get($logkey);
            $this->view ['catalogs'] = $destlist;
        } catch (Exception $ex) {
            
        }
    }

    //输出表单
    private function _outputForm($url, $fields) {
        header("Content-type: text/html; charset=utf-8");
        $form = '<html><head><title></title></head><body>';
        $form .= sprintf('<form method="post" action="%s" id="submit">' . PHP_EOL, $url);
        foreach ($fields as $k => $v) {
            $form .= sprintf('<input type="hidden" name="%s" value="%s" />' . PHP_EOL, $k, $v);
        }
        $form .= '</form></body><script type="text/javascript">document.getElementById("submit").submit();</script>';
        exit($form);
    }

    //卖家用户中心自动登录 使用时需确认 $this->user 中有值
    private function _relogin() {
        if ($_REQUEST['relogin'] && $_REQUEST['url']) {
            $encrypt = Encrypt::instance('relogin');
            $fields = array(
                'logininfo' => $encrypt->encode(json_encode($this->user)),
            );
            $this->_outputForm($_REQUEST['url'], $fields);
        }
    }

    /**
     * 根据php的$_SERVER['HTTP_USER_AGENT'] 中各种浏览器访问时所包含各个浏览器特定的字符串来判断是属于PC还是移动端
     * @author           discuz3x
     * @lastmodify    2014-04-09
     * @return  BOOL
     */
    private function _checkmobile() {
        global $_G;
        //各个触控浏览器中$_SERVER['HTTP_USER_AGENT']所包含的字符串数组
        static $touchbrowser_list = array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
            'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
            'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
            'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
            'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
            'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
            'benq', 'haier', '^lct', 'ipod', '320x320', '240x320', '176x220');

        static $mobilebrowser_list = array('windows phone','iphone', 'android');
         //wap浏览器中$_SERVER['HTTP_USER_AGENT']所包含的字符串数组
        static $wmlbrowser_list = array('cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom',
            'pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh',
            'sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte');

        $pad_list = array('pad', 'gt-p1000','ipad','ipad air');
        
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if ($this->_dstrpos($useragent, $pad_list)) {
            return false;
        }elseif ($this->_dstrpos($useragent, $mobilebrowser_list)) {
            return true;
        }elseif ($this->_dstrpos($useragent, $touchbrowser_list)) {
            return true;
        }elseif ($this->_dstrpos($useragent, $wmlbrowser_list)) {
            return true;
        }else{
            return false;
        }

        $_G['mobile'] = 'unknown';
        //对于未知类型的浏览器，通过$_GET['mobile']参数来决定是否是手机浏览器
        if (isset($_G['mobiletpl'][$_GET['mobile']])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断$arr中元素字符串是否有出现在$string中
     * @param  $string     $_SERVER['HTTP_USER_AGENT'] 
     * @param  $arr          各中浏览器$_SERVER['HTTP_USER_AGENT']中必定会包含的字符串
     * @param  $returnvalue 返回浏览器名称还是返回布尔值，true为返回浏览器名称，false为返回布尔值【默认】
     */
    private function _dstrpos($string, $arr, $returnvalue = false) {
        if (empty($string))
            return false;
        foreach ((array) $arr as $v) {
            if (strpos($string, $v) !== false) {
                $return = $returnvalue ? $v : true;
                return $return;
            }
        }
        return false;
    }

    //通过ip获取站点id，当首次进入首页时才会执行此方法
    protected function _getSiteidFromIp(){
        $this->allSites = $this->getSitesCache(1);
        $ipcityname = Ip::getCityName();
        $siteid = 0;

        if ($ipcityname && $this->allSites) {
            foreach ($this->allSites as $s) {
                if ($s ['siteName'] == $ipcityname || strpos($ipcityname, $s ['siteName']) !== FALSE) {
                    $siteid = $s ['siteId'];
                    break;
                }
            }
        }

        return $siteid ? $siteid : 72;  //当根据ip、cookie等都找不到站点时，使用上海站
    }

    private function _setUcUrl(){
        if($this->user){
            if($this->user['companyinfo']['isseller'] ==6){//地接
                $this->ucurl = 'http://' . $this->sitedomain['ground'][0] . '/';
            }elseif($this->user['companyinfo']['isseller'] ==5){//接送
                $this->ucurl = 'http://' . $this->sitedomain['take'][0] . '/';
            }else{
                $this->ucurl = $this->user['companyinfo']['isseller'] ? 'http://' . $this->sitedomain['seller'][$this->platform] . '/' : ($this->user['companyinfo']['companytype'] == 1 ? 'http://' . $this->sitedomain['buyer'][$this->platform] . '/order.largetour.html' : 'http://' . $this->sitedomain['buyer'][$this->platform] . '/orderlist.html');
            }
        }else{
            $this->ucurl = 'http://'.$this->sitedomain['index'][$this->platform].'/';
        }
    }
    
    protected function _userarea($buyercompanyid) {
        $params = array(
            'buyercompanyid'=>$buyercompanyid,//必填买家的公司ID
        );
        $result1 = $this->jrequest("api/company/info/{$params['buyercompanyid']}", self::GET)->result();
        $area = $result1['contactaddr']['areaid']?$result1['contactaddr']['areaid']:$result1['registeraddr']['areaid'];
        $area&&$result = $this->jrequest($this->servicehost['site']."api/base/address/5/{$area}", self::GET)->get();
        if($result['code']==200){
            $area = $result['result'][0]['titlepath'];
            $area = explode('/',$area);
            $area = array_pop($area);
            return $area;
        }
    }
    
    
     // 订单预订成功Url跳转
    protected function goUrl($url, $style = 0) {
        if ($style == 2) {
            header("HTTP/1.1 301 Moved Permanently");
            header('Content-type: text/html;charset=utf-8');
            header("Location:$url");
        } elseif ($style == 1) {
            echo "<script type=\"text/javascript\">window.location.href='$url';</script>";
        } else {
            header("location:$url");
        }
        exit();
    }

    
    /**
     * 根据验证规则，验证请求数据
     * @param $rule array 验证规则
     * @param $message_path string 错误信息
     * @param $method string 取参方式
     * @return bool
     */
    protected function _validationRequestData(array $rule , $message_path = '' ,$method = 'GET'){
        $validation = Validation::factory(strtoupper($method)=='POST'?$this->request->post():$this->request->query());
        foreach ($rule as $key => $value){
            foreach ($value as $k => $val){
                if(is_numeric($k)){
                    $validation->rule($key,$val);
                }else{
                    $validation->rule($key,$k,$val);
                }
            }
        }
        if(!$validation->check()){
            if(!empty($message_path)){
                $errors 		= $validation->errors($message_path);
                $this->message 	= current(array_values($errors));
            }
            $this->result 	= false;
            return false;
        }else{
            return true;
        }
    }

    public function redisCache(){
        if ($this->redisCache === null){
            $this->redisCache = new RedisCache;
        }

        return $this->redisCache;
    }

    public function _setSearchCache($key,$value){
        if($key && $value){
            $searchCache = $_COOKIE ['TRIPB2BCOM_SEARCH'];
            if($searchCache){
                $searchCache = explode(",", $searchCache);
		$searchCache = array_slice($searchCache,0,10);
            }
            $searchCache && array_unshift($searchCache,$value);
	    !$searchCache && $searchCache[] = $value;
            $searchCache = implode(",",$searchCache);
            setcookie('TRIPB2BCOM_SEARCH', $searchCache, NULL, "/", $_SERVER['SERVER_NAME']);
        }
    }
    public function _getSearchCache(){
        $searchCache = $_COOKIE ['TRIPB2BCOM_SEARCH'];
        if($searchCache){
            $searchCache = explode(",", $searchCache);
            $searchCache = array_unique($searchCache);
        }
        return $searchCache;
    }
    //用户行为记录接口
    protected function _eventAnalysis($eventid){
        if(!$_COOKIE ['TRIPB2BCOM_UUID']){
            setcookie('TRIPB2BCOM_UUID', Ip::guid(), time()+86400*180, "/", $_SERVER['SERVER_NAME']);
        }

        $params = [
            ':memberid'=>$this->user['memberinfo']['id']?:0,
            ':eventid'=>$eventid,
            ':uuid'=>$_COOKIE['TRIPB2BCOM_UUID'],
            ':ip'=>Ip::GeIpAddress(), 
            ':createtime'=>time(),
        ];
        if(ip::_isCrawler()){
            return;
        }
        $this->request('home/index', self::ADD, $params, 'line')->get();
    }
}
