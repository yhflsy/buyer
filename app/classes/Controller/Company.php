<?php

class Controller_Company extends Controller_Base {

    protected $enableRequestCache = false;
    public function before() {
        parent::before();
        //判断是否登陆
        if (!$this->user) {
            $this->showMessage('请登录后访问', 'index.html');
        } 
        $clearCache = Filter::str('cache');
        if ($clearCache == 'clear' || $clearCache == 'clears') {
            $this->enableRequestCache = true;
        }
        $this->themes = 'web.tripb2b.v3.1';
    }

    // 供应商列表
    public function action_index() {
        $companylist = $this->_companyList();
        $this->page->total($companylist['result']['totalCount']);
        if($this->siteid == "38"){
            $one = $arr = [];
            foreach ((array)$companylist['result']['list'] as $k => $value){
                if($value['companyid'] == '105815'){
                    $one[] = $value;
                }else{
                    $arr[] = $value;
                }
            }
            $companylist['result']['list'] = array_merge($one,$arr);
        }
        $this->view = $companylist;
    }
    
    // 供应商检索
    public function action_search() {
        //获取参数
        $companylist = $this->_companyList($pagesize=10);
        foreach($companylist['result']['list'] as $k => $v){
            $line['detail'] = $this->request('lines/index', self::GET, ['type'=>'hotline','website'=>$this->websitevalue[$this->platform],'siteid'=>$this->siteid,'linecategory'=>'','companyid'=>$v['companyid']], 'line')->result();
            $companyLine[$k] = array_merge($v,$line);
        }
        //将数据分配给模板
        $this->view['kwtp']=  Filter::str('kwtp');
        $this->view['keywords'] = Filter::str('keywords');
        $this->page->total($companylist['result']['totalCount']);
        $this->view['list'] = $companyLine;
        //搜索
        $this->_setSearchCache($this->view['kwtp'],$this->view['keywords']);
    }

    // 供应商详情
    public function action_detail() {
        $params = $this->_loadPost();
        if (!$params['companyid']) {
            $this->showMessage('参数不足，不能访问！', 'index.html');
        }
        //公司详情
        $companyInfo = $this->request("api/company/info/{$params['companyid']}", self::GET, [], 'member')->result();
        //客服列表
        $contactList = $this->request('system/callcenter', self::GET, ['companyid'=>$params['companyid'], 'siteid'=>$this->siteid, 'type' => 'findcallcenter'], 'line')->result();
        //判断商家是否被收藏
        $this->checkCollection($params['companyid']);
        $companyInfo && $list['companyInfo'] = $companyInfo;
        $contactList && $list['contactList'] = $contactList;
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
        $this->view['list'] = $list;
    }
    
    // 收藏商家
    public function action_ajaxFavouriteCompany() {
        $params = array(
            'category' => 1, //接口类型 创建收藏商家
            'sellerlineid' => Filter::str('id'), //商家id
            'memberid' => $this->user['memberinfo']['id'], //用户id
            'title' => Filter::str('title'), //公司标题
            'detail' => '' //备注信息
        );

        if (!$params['category'] || !$params['sellerlineid'] || !$params['memberid'] || !$params['title']) {
            echo '收藏失败';
            die;
        }
        $result = $this->request('favourite/index', self::ADD, $params)->get();
        if ($result['code'] == 200) {
            echo '200';
            die;
        }
        echo $result['message'];
        die;
    }
    
    // 商家是否被收藏
    protected function checkCollection($companyid){
        if($this->user && $companyid){
            $params = array(
                'category' => 1, //1 商家 2线路
                'sellerlineid' => $companyid, //商家ID
                'memberid'=> $this->user['memberinfo']['id'],//用户id
                'iswap' => 1 //特定参数
            );
            $code = $this->request('favourite/index', self::ADD, $params, 'line')->code();
                if($code == 'BS04010203'){
                    $collect = 1;
                }else{
                    $collect = 0;
                }
        }else{
            $collect = 0;
        }
        $this->view['collect'] = $collect;
    }
    
    //　获取供应商
    private function _companyList($pagesize=20) {
        $this->page->size = $pagesize;
        //调服务取数据
        $params = array(
            'curpageno' => $this->page->current,
            'siteid' => $this->siteid,
            'pagesize' => $pagesize, //页面size设置
            'keywords' => Filter::str('keywords', '')
        );
        if($this->websitevalue[$this->platform]!='1'){
            $params['website'] = $this->websitevalue[$this->platform];
        }
        
        $list = $this->request( "api/company/sitecy", self::ADD, $params, 'member')->get();
        if(intval($list['code']) !== 200){
            $this->showErrMessage($list['code'],$list['message'],'newcompany.html');
        }
        return $list;
    }
    
    // 初始查询条件
    private function _loadPost() {
        if(Filter::str('l')=='2'){
            $this->page->size = 15;
        }else{
            $this->page->size = 10;
        }
        $params = array(
            'website' => $this->websitevalue[$this->platform], //网站来源
            'siteid' => $this->siteid, //站点设置
            'l' => Filter::str('l'), //前台线路种类选中效果 1:国内长线，2:周边短线 3国际线路，4自由行
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
            'take' => Filter::str("take"), //接送
            'grade' => Filter::str("grade",-1), //自由行
            'traffictool' => Filter::str("traffictool"), //邮轮
            'lineprice' => Filter::str('lineprice'), //选择价格区间后会有此值返回
            'linemoon' => Filter::str('linemoon'), //选择价格区间后会有此值返回
            'sort' => Filter::str('sort',1), //排序种类 排序种类 1综合2热销3时间4价格
            'sortdown' => Filter::int('sortdown'), //是否降序
            'page' => Filter::str('p', 1), //起始页数
            'pagesize' => $this->page->size, //页面size设置
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


        if(!$params['kwtp']){
            $params['keywords'] = Filter::str('linekeywords');
        }

        if ($params['linemoon']) {
            $params['linemoon'] = $params['linemoon'];
            $params['gotimebegin'] = strtotime("2017-".$params['linemoon']."-01");
            if($params['gotimebegin']<time()){
                $params['gotimebegin'] = time();
            }
            $params['gotimeend'] = strtotime("+1 months",strtotime("2017-".$params['linemoon']."-01") );
        }else{
            $params['gotimebegin'] = "";
            $params['gotimeend'] = "";
        }
        if ($params['gobegin']) {
            $params['gotimebegin'] = strtotime($params['gobegin']);
            $params['gotimeend'] = $params['goend'] ? strtotime($params['goend']) : strtotime($params['gobegin']);
        } else {
            if ($params['goend']) {
                $params['gotimebegin'] = strtotime($params['goend']);
                $params['gotimeend'] = strtotime($params['goend']);
            }
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

    // 供应商出港地、目的地
    private function _loadLineInfo($params) {
        $result = $this->request('lines/index', self::GET , $params, 'line')->result();
        $this->view['lineinfo'] = $result;
    }
    
    // 线路搜索
    private function _loadLine() {
        $params = $this->_loadPost();

        $line = $this->request('lines/search', self::GET, $params, 'line')->get();
        if (!$line['result']['list']) {
            $line['result'] = array(
                'list' => array(),
                'total' => 0,
            );
        }

        $line = $line['result'];
        $linetypes = Kohana::$config->load("common.linequery"); //线路类型0 => 'New', 1 => '热销', 2 => '推荐', 3 => '特价', 4 => '豪华', 5 => '纯玩', 6 => '预约', 7 => '品质'
        $website = Kohana::$config->load("common.website"); //1 => '馨·驰誉', 2 => '馨·欢途', 4 => '美程
        foreach ((array)$line['list'] as $key => $value) {
            $line['list'][$key]['website'] = $website[$value['website']];
            $line['list'][$key]['category'] = $linetypes['linetype'][$value['category']];
            $line['list'][$key]['takeinfo'] = $line['list'][$key]['takeinfo']?:'';
            $line['list'][$key]['linecategory'] = $line['list'][$key]['linecategory']?:0;
        }
        //判断前台选中效果
        $line['params'] = $params;
        $line['params']['ps'] = $this->page->size;
        $line['params']['p'] = $params['page'];
        $line['params']['total'] = $line['total'];
        return $line;
    }

}
