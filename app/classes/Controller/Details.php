<?php

/**
 * 线路详情
 * User: yhf
 * Date: 2018/1/16
 * Time: 11:44
 */
class Controller_Details extends Controller_Base {
    public function before() {
        parent::before();
    }

    //线路详情
    public function action_index() {
        $this->_eventAnalysis('10007');
        $dateid = Filter::str('dateid');

        $params = array(
            'dateid' => $dateid,
            'website' => $this->websitevalue[$this->platform],
            'takeid' => Filter::int('r'), //接送id
        );

        $dateid && $list = $this->request('lines/details', self::GET, $params, 'line')->result();
        $detail = $this->_dealdetail($list['detail']); //处理详情数据

        //接送价格
        if ($this->user && $params['takeid'] >= 0) {
            $userarea = $this->_userarea($this->user['companyinfo']['id']);
        }
        $this->view['detail'] = $detail;
        $this->view['wdcategory'] = $this->_getWeidian($detail); //获取收客通分组
        $this->view['linecategory'] = Kohana::$config->load("common.linequery.linecategory");
        $this->view['urlpic'] = urlencode('http://' . Kohana::$config->load("site.params.host.zutuan") .'/'.$detail['companyid'].'/seller/line.details.html?lineid=' . $detail['lineid'].'&dateid=' . $detail['dateid'].'&r=&client=pc');
        $this->themes = 'web.tripb2b.v3.1';
    }

    //团期处理
    public function action_ajaxDate() {
        $year = Filter::int('y');
        $month = Filter::int('m');
        $dateid = Filter::int('id');
        $js = Filter::int('js');
        $prev = FALSE;
        $next = FALSE;
        $params = array('dateid' => $dateid, 'type' => 'dates');
        $dateid && $list = $this->request('lines/details', self::GET, $params, 'line')->result();
        $Data = array();
        if ($list) {
            $time = strtotime(date("Y-m-d"));
            $price = '';
            foreach ((array)$list as $key => $value) {
                $price = $js ? $value['adultprice'] : $value['adultpricemarket'];
                $Data[$key]['id'] = $value['id'];
                $Data[$key]['day'] = date('Y-m-d', $value['gotime']);
                $Data[$key]['price'] = '￥' . ($price);
                $Data[$key]['href'] = '';

                //人数验证(共享库存)
                $person = $value['surplus'];
                if ($person > 0) {
                    if ($person >= 10) {
                        $Data[$key]['state'] = "充足";
                        $Data[$key]['color'] = "green";
                    } else {
                        $Data[$key]['state'] = "余$person";
                        $Data[$key]['color'] = "blue";
                    }
                }
                $Data[$key]['title'] = $value['istakeadult'] ? '&nbsp&nbsp成人:￥' . ($value['adultpricemarket']) : '';
                $Data[$key]['title'] .= $value['istakechild'] ? '&nbsp&nbsp儿童:￥' . ($value['childpricemarket']) : '';
                $Data[$key]['title'] .= $value['istakebaby'] ? '&nbsp&nbsp婴儿:￥' . $value['babypricemarket'] : '';

                $y = date('Y', $value['gotime']);
                $m = date('m', $value['gotime']);
                if ($y < $year || ($y == $year && $m < $month)) {
                    $prev = true;
                }
                if ($y > $year || ($y == $year && $m > $month)) {
                    $next = true;
                }
            }

            echo json_encode(array('Status' => 1, 'Data' => array_values($Data), "prev" => $prev, "next" => $next), JSON_UNESCAPED_UNICODE);
            die();
        }
        echo (array("Status" => 3, "Data" => $Data, "prev" => $prev, "next" => $next));
        die();
    }

    //单天日期详情
    public function action_ajaxDaydate() {
        $dateid = Filter::str('dateid');
        $js = Filter::int('js');
        $params = array('dateid' => $dateid, 'type' => 'daydate');
        $dateid && $list = $this->request('lines/details', self::GET, $params, 'line')->result();
        $Data = [];
        if($list){
            $Data['id'] = $list[0]['id'];
            $Data['surplus'] = $list[0]['surplus'];
            $Data['platformdiscount'] = $list[0]['platformdiscount'];
            $Data['gotime'] = date('Y-m-d', $list[0]['gotime']);
            $Data['endtime'] = date('Y-m-d', $list[0]['endtime']);
            $Data['backtime'] = date('Y-m-d', $list[0]['gotime']+(($list[0]['days']-1)*60*60*24));
            $Data['istakechild'] = $list[0]['istakechild'];
            $Data['istakeadult'] = $list[0]['istakeadult'];
            $Data['istakebaby'] = $list[0]['istakebaby'];
            $Data['adultprice'] = $js ? $list[0]['adultprice'] : $list[0]['adultpricemarket'];
            $Data['childprice'] = $js ? $list[0]['childprice'] : $list[0]['childpricemarket'];
            $Data['babyprice'] = $js ? $list[0]['babyprice'] : $list[0]['babypricemarket'];
            $Data['singleroom'] = $js ? $list[0]['singleroom'] : $list[0]['singleroommarket'];
        }

        echo json_encode($Data, JSON_UNESCAPED_UNICODE);
        die();
    }

    //供应商信息
    public function action_ajaxCompany() {
        $cid = Filter::int('id');
        $cid && $companyinfo = $this->jrequest("api/company/info/{$cid}", self::GET, 'member')->result();
        if($companyinfo){
            $Data = $companyinfo;
            $connect = $this->request('system/callcenter', self::GET, ['companyid' => $cid, 'siteid' => $this->siteid, 'type' => 'findcallcenter'], 'line')->get();
            $Data['connect'] = $connect['result'];
            if (!$Data['connect']){
                $Data['connect'][0]['contactname'] = $companyinfo['responsiblename'];
                $Data['connect'][0]['mobile'] = $companyinfo['responsiblemobile'];
                $Data['connect'][0]['qq'] = $companyinfo['responsibleqq'];
            }
        }
        echo json_encode(array('Data' => $Data), JSON_UNESCAPED_UNICODE);
        die();
    }

    //处理详情数据
    private function _dealdetail($param) {
        //处理数据中的分行
        $note = array('detail','applicationnote','include','exclude','child','shopping','selffinance','attention','other','reminder','content');
        foreach ((array)$note as $k){
            if ($param[$k]) {
                $param[$k] = nl2br($param[$k]);
            }
        }

        //交通格式化
        if ($param['gotraffic']) {
            $param['gotraffic'] = explode(',',$param['gotraffic']);
        }
        if ($param['backtraffic']) {
            $param['backtraffic'] = explode(',',$param['backtraffic']);
        }

        //目的的json转地址处理
        if ($param['destination']) {
            $destarr = json_decode($param['destination'], 1);
            $dest = [];
            if(isset($destarr['city'])){
                $dest[] = $destarr['city'];
            }else{
                foreach ((array)$destarr as $ak => $av){
                    if (isset($av['city'])){
                        $dest[] = $av['city'];
                    }elseif (isset($av['province'])){
                        $dest[] = $av['province'];
                    }
                }
            }
            $dest && $param['dest'] = implode('、',$dest);
        }

        if($param['destplace']){
            $destplace = explode('-',$param['destplace']);
            $param['destplace'] = $destplace[2];
        }

        if(isset($param['linecategory'])){
            $linecategory = array('0' =>'2','1' =>'1','2' =>'3'); //l 对应线路category类型
            $param['l'] = $linecategory[$param['linecategory']];
        }

        //行程标题
        if ($param['routelist']) {
            foreach ((array)$param['routelist'] as $key=>$value){
                $param['routelist'][$key]['title'] = $this->formatfont($value['title']);
                $param['routelist'][$key]['detail'] = nl2br($value['detail']);
            }
        }

        $checkInsurance = $this->checkLineFreeInsurance($param['linecategory'],$param['days']);
        if($checkInsurance>0 && ($param['discount']['subtract']>0 || in_array($this->siteid,[153,207,457]))){
            $param['freeinsurance'] = 1;
        }else{
            $param['freeinsurance'] = 0;
        }

        $linetypes = Kohana::$config->load("common.linequery");
        $param['category'] = $linetypes['linetype'][$param['category']];

        //线路详情点击量ip
        $recordparam = [
            'lineId' => $param['lineid'],//线路ID
            'ip' => Ip::GeIpAddress(),//ip地址
            'type' => 0,//0是pc端1是手机端
            'style' =>1,//默认0广告位 1线路详情
            'siteId' => $this->siteid,//站点ID
            'siteName' => $this->allSites[$this->siteid]['siteName'],//站点名称
            'url' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],//点击的url
            'webSite' => $this->websitevalue[$this->platform],//平台
            'detail' => '',//描述
        ];
        if(!ip::_isCrawler()){ //过滤爬虫
            $this->jrequest('api/promotion/line/ad/click/record', self::ADD, $recordparam, 'promotion')->code();
        }
        return $param;
    }

    //替换标题中的图标
    protected function formatfont($title) {
        $arr = array(
            '『飞机』' => '<i class="iconfont">&#xe642;</i>',
            '『火车』' => '<i class="iconfont">&#xe644;</i>',
            '『动车』' => '<i class="iconfont">&#xe612;</i>',
            '『高铁』' => '<i class="iconfont">&#xe64c;</i>',
            '『汽车』' => '<i class="iconfont">&#xe641;</i>',
            '『游船』' => '<i class="iconfont">&#xe63f;</i>',
            '『轮船』' => '<i class="iconfont">&#xe60b;</i>',
            '『待定』' => '<i class="iconfont">&#xe645;</i>',
        );

        return str_replace(array_keys($arr), array_values($arr), $title);
    }

    private function checkLineFreeInsurance($linecategory,$days){
        $params = array(
            'webSite' =>$this->websitevalue[$this->platform] ,
            'siteId' => $this->siteid,
            'lineCategoey' => $linecategory,
        );
        $res = 0;
        $result = $this->jrequest('api/insurance/freeinsurance/rulelist', self::ADD, $params, 'insurance')->get();
        if(isset($result['code']) && $result['code'] == 200){
            foreach((array)$result['result'] as $k=>$v){
                if($days>=$v['minDay'] && $days<=$v['maxDay']){
                    $res = 1;
                }
            }
        }
        return $res?1:0;
    }
    
    //获取收客通分类
    public function _getWeidian($detail) {
        if(!$this->user){
            return;
        }
        $this->user['web'] = $this->jrequest("api/receive/wechatshop/get/{$this->user['memberinfo']['companyid']}", self::GET, array(), 'receive')->result();
        if($this->user['web']){
            header('Content-Type:text/html;charset=utf-8');
            $params = array(
                'receiveGuestId' => $this->user['web']['receiveGuestId'],
                'lineId' => $detail['lineid'],
            );
            $result = $this->jrequest('api/receive/linedetail/hasBeenCategoried', self::ADD, $params, 'receive')->get();
            if($result['code'] == '200' && $result['result']){ //已采集
                $this->user['web']['hasbeen'] = $result['result'];
                return array();
            }

            $category = $this->jrequest('api/receive/category/list/'.$this->user['web']['receiveGuestId'], self::GET,'', 'receive')->get();
            if($category['code'] == '200' && $category['result']){
                return $category['result'];
            }
        }
        return array();
    }

    //添加微店
    public function action_ajaxWeidian() {
        if(!$this->user){
            return;
        }

        $this->user['web'] = $this->jrequest("api/receive/wechatshop/get/{$this->user['memberinfo']['companyid']}", self::GET, array(), 'receive')->result();
        $params = array(
            'receiveGuestId' => $this->user['web']['receiveGuestId'],
            'companyId' => $this->user['memberinfo']['companyid'],
            'lineId' => Filter::int("lineid"),
            'categoryId' => Filter::int("id", '-1'),
        );
        $result = $this->jrequest("api/receive/linedetail/addToCategory", self::ADD, $params, "receive")->get();
        if ($result['code'] == '200') {
            echo json_encode(array('code' => $result['code'], 'msg' => $result['result']));
        } else {
            echo json_encode(array('code' => $result['code'], 'msg' => $result['result']));
        }
        exit;
    }

    //添加微店
    public function action_ajaxaddwline() {
        if(!$this->user){
            return;
        }
        $this->user['web'] = $this->jrequest("api/receive/wechatshop/get/{$this->user['memberinfo']['companyid']}", self::GET, array(), 'receive')->result();
        $params = array(
            'receiveGuestId' => $this->user['web']['receiveGuestId'],
            'companyId' => $this->user['memberinfo']['companyid'],
            'lineId' => Filter::int("lineid"),
            'categoryId' => Filter::int("id", '-1'),
        );
        $result = $this->jrequest("api/receive/linedetail/addToCategory", self::ADD, $params, "receive")->get();
        if ($result['code'] == '200') {
            echo json_encode(array('code' => $result['code'], 'msg' => $result['result']));
        } else {
            echo json_encode(array('code' => $result['code'], 'msg' => $result['result']));
        }
        exit;
    }

    public function action_ajaxPopUp() {
        $popid = Filter::int('popid');
        $poptype = Filter::str('poptype');
        if ($poptype == 'scenery') {
            $java = $this->jrequest('api/base/scenics/' . $popid, self::GET, [], 'site')->get();
        } else if ($poptype == 'hotel') {
            $java = $this->jrequest('api/base/hotels/' . $popid, self::GET, [], 'site')->get();
        }
        if (intval($java['code']) == 200) {
            $popupinfo = current($java['result']);
            $popupinfo['shortsummary'] = mb_substr($popupinfo['summary'], 0, 200);
            $popupinfo['photopath'] = current($popupinfo['photo'])['path'];
            $popupinfo['status'] = $poptype;
            echo json_encode($popupinfo, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}