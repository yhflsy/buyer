<?php

/**
 * 线路列表
 * User: yhf
 * Date: 2017/7/11
 * Time: 11:49
 */
class Controller_Lines extends Controller_Base {

    protected $enableRequestCache = false;
    public function before() {
        parent::before();

        $clearCache = Filter::str('cache');
        if ($clearCache == 'clear' || $clearCache == 'clears') {
            $this->enableRequestCache = true;
        }
    }

    //线路列表
    public function action_index() {
        $this->_eventAnalysis('10005');
        $params = $this->_loadPost();
        $this->_setSearchCache($params['kwtp'],$params['keywords']); //历史搜索

        $key = sprintf("php:lines:index:%s", md5(http_build_query($params)));
        $c = $this->redisCache();
        $request = $c->get($key);
        if (!$request || $this->enableRequestCache || 1) {
            //静态搜索项
            $this->_loadLinequery();
            //类目、出港地、目的地、供应商
            $this->_loadLineInfo($params);

            $c->set($key, $this->view, 3600);
        }else{
            $this->view = $request;
        }

        $this->search = array_merge($this->search, $params);
        $this->view['params'] = $params;
        $this->themes = 'web.tripb2b.v3.1';
    }

    //更新搜索引擎
    public function action_update() {
        $params = array(
            'siteid' => Filter::str('siteid',$this->siteid),
            'cache' => Filter::str('cache'),
        );
        $result = $this->request('lines/search', self::UPDATE , $params, 'line')->result();
        var_dump($result);die;
    }

    //清理缓存
    public function action_delete() {
        $key = "php:lines:*";
        $redis = RedisDB::getRedis();
        $arr = $redis->scan($key,$key,50000000);
        var_dump($arr);
        $redis->delete($arr);
        die;
    }

    //导出线路列表
    public function action_exportLine() {
        $line = $this->_loadLine();
        $arrTemp = array();
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/line.details.html?dateid=';
        foreach ((array)$line['list'] as $key => $value) {
            $arrTemp[$key]['num'] = $key + 1;
            $arrTemp[$key]['lineid'] = $value['lineid'];
            $arrTemp[$key]['title'][0] = $value['title'];
            $arrTemp[$key]['title'][1] = $url . $value['dateid'];
            $arrTemp[$key]['details'] = str_replace('&nbsp', '', $value['detail']);
            $arrTemp[$key]['adultpricemarket'] = $value['adultpricemarket'];
            $arrTemp[$key]['childpricemarket'] = $value['childpricemarket'];
            $arrTemp[$key]['singleroom'] = $value['singleroommarket'];
        }
        $title = array('序号', '线路ID', '产品名称', '产品亮点', '成人价', '儿童价', '单房差');
        //下列数据 $this->use 获得
        Common::exportExcelMc($title, $arrTemp, iconv('utf-8', 'gbk', "产品列表" . date('YmdHis')), array(
            'title' => $this->user['companyinfo']['companyname'],
            'columtitle' => '',
            'colum' => array(),
            'contents' => array('address' => '', 'name' => $this->user['memberinfo']['realname'], 'tel' => $this->user['memberinfo']['mobile'], 'logo' => '',),
        ));
    }

    //线路数据加载
    public function action_ajaxIndex() {
        $line = $this->_loadLine();
        if ($line) {
            $line['status'] = '1';
            echo json_encode($line, JSON_UNESCAPED_UNICODE);
            die;
        }
    }

    //热门线路
    public function action_ajaxHotline() {
        $params = array(
            'type' => 'hotline',
            'website' => $this->websitevalue[$this->platform], //网站来源
            'siteid' => $this->siteid,
            'linecategory' => Filter::str('linecategory'),
            'companyid' => Filter::str('companyid',''), //供应商ID
        );

        $key = sprintf("php:lines:hotline:%s", md5(http_build_query($params)));
        $c = $this->redisCache();
        $hotline = $c->get($key);
        if (!$hotline || !$this->enableRequestCache) {
            $hotline = $this->request('lines/index', self::GET, $params, 'line')->result();
            foreach ($hotline as $key => $value) {
                $hotline[$key]['title'] = $value['title'] . '' . $value['subtitle'];
                $hotline[$key]['title'] = mb_substr($hotline[$key]['title'], 0, 15);
                if($key<1){
                    $hotline[$key]['title'] = Common::showTitle($hotline[$key]['title'], 22);
                    $value['photo'] && $hotline[$key]['photo'] = str_replace('.', '_160x100.', $value['photo']);
                }else{
                    $value['photo'] && $hotline[$key]['photo'] = str_replace('.', '_50x50.', $value['photo']);
                }
            }
            $c->set($key, $hotline, 36000);
        }

        if ($hotline) {
            $hotline['detail'] = $hotline;
            echo json_encode($hotline, JSON_UNESCAPED_UNICODE);
            die;
        }
    }

    //初始查询条件
    private function _loadPost() {
        if(Filter::str('l')=='2'){
            $this->page->size = 15;
        }else{
            $this->page->size = 10;
        }
        $params = array(
            'website' => $this->websitevalue[$this->platform], //网站来源
            'siteid' => $this->siteid, //站点设置
            'l' => Filter::str('l'), // //前台线路种类选中效果 1:国内长线，2:周边短线 3国际线路，4自由行;
            'linecategory' => Filter::str('linecategory'), //线路种类 0 周边短线，1 国内长线,2 国际线路,4自由行
            'kwtp' => Filter::int('kwtp'), //关键字搜索
            'keywords' => Filter::str('keywords', ''), //关键字搜索
            'export' => Filter::str('export'), //是否导出 传1为需要导出 　
            'dest' => Filter::str('dest'),
            'departure' => Filter::str('departure'), //出发城市
            'destination' => Filter::str('destination'),
            'companyid' => Filter::str('companyid'), //商家名称搜索
            'companyname' => Filter::str('companyname'), //商家名称搜索
            'days' => Filter::str('days'), //天数
            'gobegin' => Filter::str('gobegin'), //出团开始时间
            'goend' => Filter::str('goend'), //出团结束日期
            'pbegin' => Filter::str('pbegin'), //开始价格
            'pend' => Filter::str('pend'), //结束价格
            'take' => Filter::str("take"), // 接送
            'labelid' => Filter::str("labelid"), // 玩法
            'grade' => Filter::str("grade",-1), // 自由行
            'traffictool' => Filter::str("traffictool"), // 邮轮
            'lineprice' => Filter::str('lineprice'), //选择价格区间后会有此值返回
            'linemoon' => Filter::str('linemoon'), //选择价格区间后会有此值返回
            'sort' => Filter::str('sort'), //排序种类 排序种类 1综合2热销3时间4价格
            'sortdown' => Filter::int('sortdown'), //是否降序
            'page' => Filter::str('p', 1), //起始页数
            'pagesize' => $this->page->size, //页面size设置
            'cache' => Filter::str('cache'),
            'stagepay' => Filter::str('stagepay'),
            'traffictool' => Filter::str('traffictool'),
            'instantconfirm' => Filter::str('instantconfirm'),
            'category' => Filter::str('category'),
        );
        switch ($params['l']) {
            case '4':
                $params['grade'] = 1;
                $params['linecategory'] = "";
                break;
            case '5':
                $params['traffictool'] = 8;
                break;
            default:
                $linecategory = array('1' =>'1','2' =>'0','3' =>'2'); //l 对应线路category类型
                $params['linecategory'] = $linecategory[$params['l']];
                break;
        }


        $linekeywords = Filter::str('linekeywords');
        if($linekeywords!=$params['keywords'] && $linekeywords){
            $params['keywords'] = Filter::str('linekeywords');
        }



        if ($params['linemoon']) {
            $params['linemoon'] = $params['linemoon'];
            $year = date("Y");
            $params['gotimebegin'] = strtotime($year."-".$params['linemoon']."-01");
            if($params['gotimebegin']<time()){
                $params['gotimebegin'] = time();
            }
            $params['gotimeend'] = strtotime("+1 months -1 day",strtotime($year."-".$params['linemoon']."-01") );
        }
        
        if ($params['gobegin']) {
            $params['gotimebegin'] = strtotime($params['gobegin']);
        }
        if ($params['goend']) {
            $params['gotimeend'] = strtotime($params['goend']);
        }

        if ($params['lineprice'] && !$params['pbegin'] && !$params['pend']) {
            $lineprice2 = Kohana::$config->load("common.linequery.lineprice2");
            $params['pricebegin'] = $lineprice2[$params['lineprice']][0];
            $params['priceend'] = $lineprice2[$params['lineprice']][1];
        }

        if ($params['pbegin']) {
            $params['pricebegin'] = $params['pbegin'];
        }

        if ($params['pend']) {
            $params['priceend'] = $params['pend'];
        }

        if ($this->user) {
            $params['takearea'] = $this->_userarea($this->user['companyinfo']['id']); //接送区域价格匹配
        }
        return $params;
    }

    //线路搜索
    private function _loadLine() {
        $params = $this->_loadPost();

        $line = $this->request('lines/search', self::GET, $params, 'line')->get();
        if (!$line['result']['list']) {
            $line['result'] = array(
                'list' => array(),
                'total' => 0,
            );
        }

        //是否有免费保险
        $checkInsurance = $this->_loadFreeInsurance();

        $line = $line['result'];
        $linetypes = Kohana::$config->load("common.linequery"); //线路类型0 => 'New', 1 => '热销', 2 => '推荐', 3 => '特价', 4 => '豪华', 5 => '纯玩', 6 => '预约', 7 => '品质'
        $website = Kohana::$config->load("common.website"); //1 => '馨·驰誉', 2 => '馨·欢途', 4 => '美程
        foreach ((array)$line['list'] as $key => $value) {
            $line['list'][$key]['website'] = $website[$value['website']];
            $line['list'][$key]['category'] = $linetypes['linetype'][$value['category']];
            $line['list'][$key]['takeinfo'] = $line['list'][$key]['takeinfo']?:'';
            $line['list'][$key]['linecategory'] = $line['list'][$key]['linecategory']?:0;
            $line['list'][$key]['isJapan'] = strpos($line['list'][$key]['destplace'],'日本') === false ? 0 : 1;
            //免费保险
            $freeinsurance = 0;
            $days = $line['list'][$key]['days'];
            $freeinsurancearr = $checkInsurance[$line['list'][$key]['linecategory']];
            foreach((array)$freeinsurancearr as $k=>$v){
                if($days>=$v['minDay'] && $days<=$v['maxDay']){
                    $freeinsurance = 1;
                }
            }
            if($freeinsurance>0 && ($value['discount']['subtract']>0 || in_array($this->siteid,[153,207,457]))){
                $line['list'][$key]['freeinsurance'] = 1;
            }else{
                $line['list'][$key]['freeinsurance'] = 0;
            }

            $line['list'][$key]['hongbaoprice'] = (int)$value['hongbaoprice'];
        }

        //判断前台选中效果
        $line['params'] = $params;
        $line['params']['ps'] = $this->page->size;
        $line['params']['p'] = $params['page'];
        $line['params']['total'] = $line['total'];
        return $line;
    }

    // 静态搜索项
    private function _loadLinequery() {
        $this->view['linequery'] = Kohana::$config->load('common.linequery');
        $this->view['website'] = Kohana::$config->load('common.website');

        //月份
        $month = (int) date('m',time());
        while ($month<=12){
            $months[$month] = $month;
            $month++;
        }
        $this->view['linequery']['linemoon'] = $months;
    }

    //类目、出港地、目的地、供应商
    private function _loadLineInfo($params) {
        //线路类型
        $linetype = array('1' =>'domestic','2' =>'shortline','3' =>'abroad');
        $linetype = $linetype[$params['l']];

        //读取类目缓存
        $logkey = sprintf("php:home:catalogs:%s", md5(sprintf("%s,%s", $this->siteid, $this->platform)));
        $c = $this->redisCache();
        $destlist = $c->get($logkey);

        if(!$destlist){
            $catparams = array(
                'website' => $this->websitevalue[$this->platform],
                'siteid' => $this->siteid, //站点设置;
            );
            $destlist = Controller_Homeservice::getCategroys($catparams,$this);
            $c->set($logkey, $destlist, 7200);
        }

        foreach ((array)$destlist[$linetype]['items'] as $k => $v) {
            foreach ((array)$v['list'] as $v1) {
                $dest[$v1['title']] = $v1;
            }
        }
        $this->view['categorylist'] = (array)$destlist[$linetype]['items'];
        $this->view['category'] = $dest;

        //周边，国内,国际未选中专线 默认不显示
        if(!empty($linetype) && empty($params['dest'])){
            return;
        }
        //出港地、目的地、供应商
        $result = $this->request('lines/index', self::GET , $params, 'line')->result();
        $this->view['lineinfo'] = $result;
    }
    
    public function action_ajaxline(){
        $ip = Ip::GeIpAddress();
        $params = [
            'lineId' => Filter::str('id'),//线路ID
            'ip' => $ip,//ip地址
            'type' => 0,//0是pc端1是手机端
            'style' =>2,//默认0广告位 1线路详情
            'siteId' => $this->siteid,//站点ID
            'siteName' => $this->allSites[$this->siteid]['siteName'],//站点名称
            'url' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],//点击的url
            'webSite' => $this->websitevalue[$this->platform],//平台
            'detail' => '',//描述
        ];
        $data = $this->jrequest('api/promotion/line/ad/click/record', self::ADD, $params, 'promotion')->code();
        exit;
    }

    //免费保险
    private function _loadFreeInsurance(){
        $res[0] = $this->checkLineFreeInsurance(0);
        $res[1] = $this->checkLineFreeInsurance(1);
        $res[2] = $this->checkLineFreeInsurance(2);
        return $res;
    }
    private function checkLineFreeInsurance($linecategory){
        $params = array(
            'webSite' =>$this->websitevalue[$this->platform] ,
            'siteId' => $this->siteid,
            'lineCategoey' => $linecategory
        );

        $res = [];
        $result = $this->jrequest('api/insurance/freeinsurance/rulelist', self::ADD, $params, 'insurance')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
}