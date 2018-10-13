<?php

class Controller_Admin_Order extends Controller_Admin_Orderbase {

    protected $orderstatus; //订单类型

    public function before() {
        parent::before();
        $this->orderstatus = Kohana::$config->load('common.orderstatus');
    }

    // 散客订单
    public function action_index() {
        $this->_eventAnalysis('10015');
        $this->_check(11101);
        if( $this->user['companyinfo']['companytype'] == 1 &&  $this->user['companyinfo']['isseller']==0 ){
            Header("HTTP/1.1 303 See Other"); 
            $url = "order.largetour.html";
            Header("Location: $url"); 
            exit; 
        }
        $urlParams = '';
        Filter::int('state')>1 && $urlParams .='state='.Filter::int('state');
        Filter::int('mg') &&  $urlParams.= '&mg='.Filter::int('mg');
        $url = "orderlist.html?".$urlParams;
        Header("Location: $url"); 
        exit; 
        $this->search = $search = array(
            'kw' => Filter::str('kw'), // 搜索关键字
            'ordernum' => Filter::str('ordernum'), // 订单编号
            'ispay' => Filter::int('ispay', -10), //支付状态
            'paystyle' => Filter::int('paystyle'), //支付类型
            'state' => Filter::int('state', -10), // 订单状态
            'ltype' => Filter::int('ltype', -1), // 线路类型
            'buyerid' => Filter::int('buyerid'), // 计调
            'createdate' => Filter::str('createdate'), // 下单时间段
            'godate' => Filter::str('godate'), // 出团时间段
            'configrmdate' => Filter::str('configrmdate'), // 确认时间段
            'paydate' => Filter::str('paydate'), // 支付时间段
            'ordersite' => Filter::int('ordersite'), // 来源
            'gname' => Filter::str('gname'), // 游客
            'mg' => Filter::int('mg', -1), //消息
//            'flag' => Filter::int('flag', 0), // 支付方式
            'companykw' => Filter::str('companykw'), // 供应商
            'operator' => 4, //用于计调搜索
            'dateid' => Filter::int('dateid'),
            'isinvoice' => Filter::int('isinvoice'),
            'isActivity' => Filter::int('isActivity', 0),//是否参与活动
            'ishidecancel' => Filter::int('ishidecancel', 1),//是否隐藏取消
            'cancel' =>  Filter::int('cancel',1),
            'fenqi' => Filter::int('fenqi'),
            'priceishidden' => Filter::int('hidden', 0),
        );
        $ispay = $this->jrequest("api/company/info/{$this->user['companyinfo']['id']}", self::GET)->result();
        
        $this->_searchMessage($search);
        // 搜索处理
        $this->_search($search);
        $params = array(
            'type' => 'list',
            'flag' => Filter::int('flag', 0), // 支付方式
            'companyid' => $this->user['companyinfo']['id'],
            'platforms' => 1,
            'search' => json_encode($search),
            'page' => Filter::int('p'),
            'pagesize' => Filter::int('ps',20),
            'export' => Filter::int('export'), //导出列表标识
        );
        if (!in_array(11801, json_decode($this->roles['roleids']))) {   //判断用户是否拥有查看所有订单权限
            $params['memberid'] = $this->user['memberinfo']['id'];
        }
        $data = [];
        $data = $this->request('order/index', self::GET, $params, 'order')->get();        
        $this->_changer($data);
        // 列表处理
        $siteid = [];//站点id
        $this->_matchLine($data,$params['export'],$members,$siteid);
        //如果不是管理员，计调只显示自己
        if(!$data['list']){
            $members = $this->jrequest('api/member/list', self::ADD, ['companyid' => $this->user['companyinfo']['id'], 'pagesize' => 100, 'curpageno' => 1])->get(); 
        }
        if(!in_array(202, $this->order202)){
            foreach ($members['result']['list'] as $k => $v) {  
                if(!in_array((int)$this->user['memberinfo']['id'], $v)){
                    unset($members['result']['list'][$k]);
                }
            }
        }
        //合同
        if($data['list']){
            $data['list'] = $this->_getContract($data['list']);
        }
//        //专车
//        if($data['list']){
//            $data['list'] = $this->_getTakeFleet($data['list']);
//        }
        //信诚支付
        if($data['list']){
            $data['list'] = $this->_getXincheng($data['list']);
        }

//        foreach((array)$data['list'] as $key=>$value){
//            $line = $this->request('line/index', self::GET, ['dateid' => $value['lineid'], 'type' => 'dateDetail'], 'line')->result();
//            $data['list'][$key]['destplace'] = strpos($line['detail']['destplace'],'日本') === false ? 0 : 1;
//        }

        //循环判断是否参加活动
        /*
        foreach($data['list'] as $key=>$val){
            $data['list'][$key]['hasAct']=0;
            $lineDetail=$this->_lineDetail($val['lineid']);
            $activityInfo=$this->jrequest('api/promotion/seller/activity/line/'.$lineDetail['companyid'].'/'.$lineDetail['lineid'].'/'.$lineDetail['website'].'/'.$lineDetail['siteid'],self::GET,'','promotion')->result(); //获取线路活动
            if($activityInfo){
                $data['list'][$key]['hasAct']=1;
            } 
        }
         * 
         */

        //判断是否有可用驰誉卷
        $couponlist = $this->request('coupon', self::GET, ['type'=>'getCoupon','memberid'=>$this->user['memberinfo']['id']], 'base')->result();
        $checkcoupon = $couponlist['list']?1:0;
        //输出处理
        $this->page->total($data['total']);
        $this->view = array(
            'list' => (array) $data['list'],
            'orderstate' => $this->orderstatus,
            'ispay' => array('1'=>'已付','0'=> '未付'),
            'members' => $members['result']['list'],
            'ltype' => $this->search['ltype'],
            'refundtype' => Kohana::$config->load('common.refundtype'),
            'color' => array('gray','cyan','orange','green','blue'),
            'siteinfo' => $this->_getSiteName($siteid),  //站点
            'cityid' => $this->getCityId($this->user['memberinfo']['companyid']),
            'params' => $search,
            'checkcoupon' => $checkcoupon,
            'buyerispay'=>$ispay['ispay']
        );
        //导出
        $params['export'] &&  $this->_export($data,$search);
    }
    
    //根据站点id获取订单下的站点名称
    private function _getSiteName(array $params){
        $siteinfo = $this->jrequest('api/base/sites/list/'.  implode(',', $params), self::GET, '', 'site')->get();//获取站点信息
        $siteName = [];
        if($siteinfo['result']){
            foreach ($siteinfo['result'] as $value) {
                $siteName[$value['siteId']] = $value['siteName'];
            }
        }
        return $siteName;
    }
    
    // 搜索处理
    private function _search(&$search){
        $time = $_SERVER['REQUEST_TIME'];
        list($search['createbe'], $search['createed']) = explode('至', $search['createdate']);
        list($search['gobe'], $search['goed']) = explode('至', $search['godate']);
        // 大社月结算处理
        if($this->user['companyinfo']['companytype'] == 1 && $search['ltype'] == 3){
            $d = date("d",$time);
            $y = date("Y",$time);
            $m = date("m",$time);
            $m = $d > 10 ? $m : $m-1 ;
            $search['configrmbe'] = date("Y-m-d", mktime(0, 0, 0, $m, 1, $y));
            $search['configrmed'] = date("Y-m-d", mktime(0, 0, 0, $m+1, 0, $y));
            $this->search['configrmdate'] = $search['configrmbe']."至".$search['configrmed'];
            $this->view['time'] = mktime(23, 59, 59, $m+1, 10, $y) - $time;
        }else{
            list($search['configrmbe'], $search['configrmed']) = explode('至', $search['configrmdate']);
        }
        // 供应商搜索处理
        if(!empty($search['companykw'])){
          $companyids = $this->jrequest("/api/company/query/".urlencode($search['companykw'])."/1", self::GET)->get(); 
          $companyids['result'] && $search['sellercompanyid'] = implode(',',array_unique(Arr::pluck($companyids['result'], 'id')));
        }
        list($search['paybe'], $search['payed']) = explode('至', $search['paydate']);
    }
    
    // 列表处理
    private function _matchLine(&$aOrderList,$export,&$members,&$site_id) {
        $timestamp = $_SERVER['REQUEST_TIME'];
        $members = [];
        if ($aOrderList['list']) {
            $buyermemberids = [];
            $sellermemberids = [];
            $sellercompanyids = [];
            $autoplatform = ['1' => 'tripb2b','2'=>'happytoo','4' => 'mconline'];
            // 获取用户列表
            $members = $this->jrequest('api/member/list', self::ADD, ['companyid' => $this->user['companyinfo']['id'], 'pagesize' => 100, 'curpageno' => 1],"member")->get();

            if (isset($aOrderList['list']) && is_array($aOrderList['list'])) {
                $sellermemberids = array_unique(Arr::pluck($aOrderList['list'], 'selleropeartorid'));
                if($this->user['companyinfo']['companytype'] == 1){
                    $buyermemberids = array_unique(Arr::pluck($aOrderList['list'], 'buyerid'));
                    $sellermemberids = array_merge($buyermemberids, $sellermemberids);
                }
            }
            if (isset($members['result']['list']) && is_array($members['result']['list'])) {
                $buyermemberids = array_unique(Arr::pluck($members['result']['list'], 'id'));
            }
            // 获取用户参数处理
            if (is_array($buyermemberids) || is_array($sellermemberids)) {
                $memberids = implode(',', array_merge($buyermemberids, $sellermemberids));
            }
            // 用户处理
            $memberlist = $this->jrequest('api/member/'.$memberids, self::GET,[],"member")->get();
            if (isset($memberlist['result']) && is_array($memberlist['result'])) {
                $memberlist = array_combine(Arr::pluck($memberlist['result'], 'id'), $memberlist['result']);
            }
            // 代金券处理
            foreach ($aOrderList['list'] as $v) {
                $orderlist[$v['id']]=['siteid' =>$v['siteid'], 'ordersite' =>$v['ordersite']];
            }
            $coupon = $this->request('coupon', self::GET, ['type' => 'uselist', 'data' =>$orderlist], 'base')->result();
            
            if(!$export){
                // 获取订单ids
                $ids = array_unique(Arr::pluck($aOrderList['list'], 'id'));
                $ids = implode(',',$ids);
                $finance = $this->request('finance/buyer', self::GET, ['orderid' => $ids, 'type' => 'more'],'finance')->get();
                $this->_changer($finance);
                $finance = array_combine(Arr::pluck($finance, 'orderid'), $finance);
                $lineids = array_unique(Arr::pluck($aOrderList['list'], 'lineid'));
                $aLineList = $this->request('line/index', self::GET, ['id' => $lineids, 'type' => 'linelistBydateids'], 'line')->get();
                $this->_changer($aLineList); 
                // 保险处理
                $insurance = $this->jrequest('api/insurance/order/lineorder/select', self::ADD, ['lineOrderIds' => $ids],"insurance")->result();
                
                $sellercompanyids = array_unique(Arr::pluck($aOrderList['list'], 'sellercompanyid'));
                //鸿运发票处理
                if (is_array($sellercompanyids)) {
                    $sellcompanyids = implode(',', $sellercompanyids);
                }
                //鸿运发票处理
//                $hyinrule = Controller_Admin_Orderservice::gethyinvorule(array('companyids' => $sellcompanyids,),$this);
                $hyinorder = Controller_Admin_Orderservice::checkorderinvo(array('orderIds' => $ids),$this);
            }
            foreach ($aOrderList['list'] as $k => $v) {
                // 线路处理
                if(isset($aLineList['list'][$v['lineid']])){
                    $aOrderList['list'][$k]['gocity'] =  $aLineList['list'][$v['lineid']]['gocity'];
                    $aOrderList['list'][$k]['brand'] = $aLineList['list'][$v['lineid']]['brandtitle'];
                    $aOrderList['list'][$k]['subtitle'] = $aLineList['list'][$v['lineid']]['subtitle'];
                    $destination = json_decode($aLineList['list'][$v['lineid']]['destination'], true);
                    $aOrderList['list'][$k]['destination'] = $destination['city']?$destination['city']:$destination[0]['city'];
                    $aOrderList['list'][$k]['linegotraffic'] = $aOrderList['list'][$k]['gotraffic'] ? $aOrderList['list'][$k]['gotraffic'] : $aLineList['list'][$v['lineid']]['gotraffic'];
                    $aOrderList['list'][$k]['linebacktraffic'] = $aOrderList['list'][$k]['backtraffic'] ? $aOrderList['list'][$k]['backtraffic'] : $aLineList['list'][$v['lineid']]['backtraffic'];
                }
                //保险处理
                if($insurance){
                    $aOrderList['list'][$k]['insurance'] =  $insurance[$v['id']] ?: "";
                }
                // 计调处理
                $aOrderList['list'][$k]['selleropeartorname'] = isset($memberlist[$v['selleropeartorid']]['realname']) ? $memberlist[$v['selleropeartorid']]['realname'] : "--";
                $aOrderList['list'][$k]['selleropeartorcompanyname'] = isset($memberlist[$v['selleropeartorid']]['companyname']) ? $memberlist[$v['selleropeartorid']]['companyname'] : "--";
                $aOrderList['list'][$k]['selleropeartormobile'] = isset($memberlist[$v['selleropeartorid']]['mobile']) ? $memberlist[$v['selleropeartorid']]['mobile'] : "--";
                $aOrderList['list'][$k]['buyername'] = isset($memberlist[$v['buyerid']]['realname']) ? $memberlist[$v['buyerid']]['realname'] : "--";
                $aOrderList['list'][$k]['selleropeartorusernamee'] = isset($memberlist[$v['selleropeartorid']]['username']) ? $memberlist[$v['selleropeartorid']]['username'] : "--";
                
                $aOrderList['list'][$k]['appraise'] = $timestamp> ($aOrderList['list'][$k]['backtime'] + 86400 ) ? 1 : 0; // 评价
                //$aOrderList['list'][$k]['refund'] = ($timestamp - $aOrderList['list'][$k]['backtime']) > 86400 ? 1 : 0;  // 退款
                $aOrderList['list'][$k]['refund'] = (date('Ymd',$timestamp) - date('Ymd',$aOrderList['list'][$k]['backtime'])) >0 ? 1 : 0;  // 退款
                $aOrderList['list'][$k]['refund15days'] = ($timestamp - $aOrderList['list'][$k]['backtime']) >3600*24*30 ? 1 : 0;;  // 回团15天后没有停止退款操作
                // 代金券
                $actInfo = $this->jrequest('/api/promotion/seller/activity/line/'.$aOrderList['list'][$k]['sellercompanyid'].'/'.$aLineList['list'][$v['lineid']]['lineid'].'/'.$aOrderList['list'][$k]['ordersite'].'/'.$aOrderList['list'][$k]['siteid'], self::GET, '', 'promotion')->get();
                if(isset($coupon[$v['id']]['id']) || ($actInfo['code'] == 200 && is_array($actInfo['result']))){
                     $aOrderList['list'][$k]['coupon'] = 1;
                }else{
                     $aOrderList['list'][$k]['coupon'] = 0;
                }
//                $aOrderList['list'][$k]['coupon'] = isset($coupon[$v['id']]['id']) ? 1 : 0;
                // 财务连接
                if($this->_financePay($finance, $v['id']) ){
                    if($aOrderList['list'][$k]['payamount'] > 0){
                       $aOrderList['list'][$k]['financepay'] = 0; 
                    }else{
                       $aOrderList['list'][$k]['financepay'] = $this->_financePay($finance, $v['id']); 
                    }
                }
                if($this->_ispayed($finance, $v['id'])){
                    $aOrderList['list'][$k]['ispay'] = 1;
                }
                // 大社立减处理
                if($this->user['companyinfo']['companytype']==1 && date('Ymd',$aOrderList['list'][$k]['confirmtime']) < 20160301){
                    $aOrderList['list'][$k]['ret'] = OrderPay::payDiscount($v,1);
                }else{
                    $aOrderList['list'][$k]['ret'] = $this->_payTime($v);
                }
                $aOrderList['list'][$k]['movetoplatform'] = $autoplatform[$aOrderList['list'][$k]['ordersite']];// 订单跳转平台
                //收集所有站点id
                $site_id[] = $v['siteid'];
                //鸿运发票，隐藏
                $aOrderList['list'][$k]['ishyorder'] = $hyinorder[$v['id']];                
//                $aOrderList['list'][$k]['ishyflag'] = $hyinrule[$aOrderList['list'][$k]['sellercompanyid']]['status'];
//                $aOrderList['list'][$k]['ishyflag'] = 0;
            }
            $site_id = $site_id ? array_unique($site_id) : [];
        }
    }
    // 立减处理
    private function _payTime($order){
        $ret = ['countdown' => 0, 'tips' => '', 'discount' => 0,'tipico' =>''];
        if (isset($order['state']) && ! in_array($order['state'], [1, 4])) {
            return $ret;
        }
        if (isset($order['state']) && $order['ispay']){
            return $ret;
        }
        $order['ordersite'] = $order['category'] == 1 ? 1 : $order['ordersite'];
        $timestamp = $_SERVER['REQUEST_TIME'];
        $params = array(
                    'dateid' => $order['lineid'],
                    'type' => 'dateDetail'
                );
        $line = $this->request('line/index', self::GET, $params, 'line')->result();
        $actInfo = $this->jrequest('/api/promotion/seller/activity/line/'.$order['sellercompanyid'].'/'.$line['detail']['id'].'/'.$order['ordersite'].'/'.$order['siteid'], self::GET, '', 'sktlijian')->result();
        if ($actInfo && $actInfo != null) {
            $result['platformHour'] = $actInfo['subtractConfirmHour'];
            $result['sellerHour'] = $actInfo['subtractConfirmHour'];
            $result['platformPercent'] = $actInfo['platformPercent'];
            $result['sellerPercent'] = $actInfo['sellerPercent'];
        } else {
            $sktlijian = $this->jrequest('api/promotion/minus/showCoupon/' . $order['ordersite'] . '/' . $order['siteid'], self::GET, '', 'sktlijian')->result();
            $result = $this->request('order/pay', self::GET, ['webSite' => $order['ordersite'], 'siteId' => $order['siteid'], 'sellerId' => $order['sellercompanyid'], 'buyerId' => $order['buyercompanyid'], 'type' => "getDiscount"], 'order')->result();
        }
        $hour =  $result['platformHour'] >= $result['sellerHour'] ? ($result['platformHour']>=$sktlijian[0]['hour']?$result['platformHour']:$sktlijian[0]['hour']) : ($result['sellerHour']>=$sktlijian[0]['hour']?$result['sellerHour']:$sktlijian[0]['hour']);
//        if($result['platformHour'] || $result['sellerHour']){
//            $hour = $result['platformHour'] > $result['sellerHour'] ? $result['platformHour'] : $result['sellerHour'];
//            $countdown = $hour*3600+$order['confirmtime'] - $timestamp;
//        }
//        if(($result['platformHour'] || $result['sellerHour']) && ($result['platformHour'] == $result['sellerHour'])){
//            $percent = $result['platformPercent'] + $result['sellerPercent'];
//            $percent += $result['platformAdditionalPercent'] ?: 0;
//            
//        }else if(($result['platformHour'] || $result['sellerHour']) && ($result['platformHour'] > $result['sellerHour'])){
//            $percent = ($result['sellerHour']*3600+$order['confirmtime'] - $timestamp) > 0 ? ($result['platformPercent'] + $result['sellerPercent'] + $result['platformAdditionalPercent']) : $result['platformPercent'];
//
//        }else if( ($result['platformHour'] || $result['sellerHour']) && ($result['platformHour'] < $result['sellerHour'])){
//            $percent = ($result['platformHour']*3600+$order['confirmtime'] - $timestamp) > 0 ? ($result['platformPercent'] + $result['sellerPercent'] + $result['platformAdditionalPercent']) : ($result['sellerPercent']+ $result['platformAdditionalPercent']);
//       
//        }else if($result['parentSellerHour']){
//            $companyinfo = $this->jrequest('api/company/info/' .$order['sellercompanyid'], self::GET, '', 'uccompany')->result(); //取旗舰店id
//            $companyinfo['parentid'] && $flagship = $this->jrequest('api/company/info/' . $companyinfo['parentid'], self::GET, '', 'uccompany')->result(); //取旗舰店id
//            if ($flagship['companytype'] == 1) {
//                $discountPercent = $result['parentPlatformPercent'] + $result['parentSellerPercent'] + $result['platformPercent'];
//                $discountPercent = $discountPercent ? $discountPercent :$result['sumPercent'];
//            }else if ($flagship['companytype'] == 10) {
//                $lingbrandid = $this->request('line/index', self::GET, ['dateid' => $order['lineid'], 'type' => 'brandid'], 'line')->result(); //取品牌id
//                $lingbrandid && $branddiscount = $this->request('line/brand', self::GET, ['companyid' => $companyinfo['parentid'], 'id' => $lingbrandid, 'type' => 'companyid'], 'line')->result(); //筛选是否有品牌
//                if ($branddiscount ) {
//                   $discountPercent = $result['parentPlatformPercent'] + $result['parentSellerPercent'] + $result['platformPercent'];
//                }
//                $discountPercent = $discountPercent ? $discountPercent :$result['sumPercent'];
//            }
//            $percent = ($result['parentSellerHour']*3600+$order['confirmtime'] - $timestamp) > 0 ? $discountPercent : $result['sumPercent'];
//        }
        $platformHour =0;
        $sellerHour=0;
        $parentSellerHour =0;
        $sktHour=0;
        $platformHour = $result['platformHour'] * 3600+$order['confirmtime'] - $timestamp;
        $sellerHour =  $result['sellerHour'] * 3600+$order['confirmtime'] - $timestamp;
        !$sellerHour && $parentSellerHour = $result['parentSellerHour'] * 3600+$order['confirmtime'] - $timestamp;
        $order['category'] ==1 && $sktHour = $sktlijian[0]['hour'] * 3600+$order['confirmtime'] - $timestamp;
        if ($platformHour > 0 && ($platformHour <= $sellerHour || $sellerHour <= 0) && ($platformHour <= $parentSellerHour|| $parentSellerHour<= 0 ) && ($platformHour <= $sktHour || $sktHour<= 0)) {
            $countdown = $platformHour;
        } else if ($sellerHour > 0 && ($sellerHour <= $platformHour || $platformHour<=0) && ($sellerHour <= $sktHour || $sktHour<= 0)) {
            $countdown = $sellerHour;
        } else if (!$sellerHour && $parentSellerHour > 0 && ($parentSellerHour <= $platformHour || $platformHour<=0)  && ($parentSellerHour <= $sktHour || $sktHour<= 0)) {
            $countdown = $parentSellerHour;
        } else if ($sktHour > 0 && ($sktHour <= $platformHour || $platformHour <=0) && ($sktHour <= $sellerHour || $sellerHour<=0) && ($sktHour <= $parentSellerHour || $parentSellerHour<=0)) {
            $countdown = $sktHour;
        }
        $percent = 0;
        $order['category'] ==1 && $sktHour >0  &&  $percent += $sktlijian[0]['percent'];
        $platformHour > 0 && $percent += $result['platformPercent'];
        $sellerHour > 0 && $percent += $result['sellerPercent'] + $result['platformAdditionalPercent'];

        !$result['sellerPercent'] && $parentSellerHour > 0 && $percent += $result['parentSellerPercent'] + $result['parentPlatformPercent'];
        if($countdown > 0 && date("Ymd",$order['confirmtime']) < date("Ymd",$order['gotime'])){
            $ret['countdown'] = $countdown; 
            $ret['tips'] ="之前支付 立减{$percent}%(指定期限之前支付有效）";;
            $ret['tipico'] = "&#xe8c8;";
            $ret['platformHour'] = $platformHour;
            $ret['sellerHour'] = $sellerHour;
            $ret['parentSellerHour'] = $parentSellerHour;
            $ret['platformPercent'] =  $result['platformPercent'] +  $result['platformAdditionalPercent'];
            $ret['sellerPercent'] =  $result['sellerPercent'];
            $ret['parentSellerPercent'] =  $result['parentSellerPercent'] +  $result['parentPlatformPercent'];
            if ($order['category'] = 1) {
                $ret['sktHour'] = $sktHour;
                $ret['percent'] = $sktlijian[0]['percent'];
            }
        }elseif($hour){
            $ret['tipico'] = "&#xe8c8;";
            $ret['tips'] = "出团当天下单/支付或订单确认后超过{$hour}小时未支付，不享受立减！";
        }

        $nowtime = time();
        $confirmtime = 24 * 3600+$order['confirmtime'];
        if($line['detail']['platformdiscount']>0){
            $ret = [];
            $ret['tipico'] = "&#xe8c8;";
            if(($line['detail']['gotime']+86399)>$nowtime && $confirmtime>$nowtime && $order['confirmtime']<$line['detail']['gotime']){
                $platformdiscount = $line['detail']['platformdiscount'];
                if($countdown > 0){
                    $platformdiscount += $result['sumPercent'];
                }
                $ret['countdown'] = $confirmtime - $timestamp;
                $ret['platformdiscount'] = $platformdiscount;
                $ret['tips'] = "之前支付 立减{$platformdiscount}%(指定期限之前支付有效）";
            }else{
                $ret['tips'] = "出团当天下单/支付或订单确认后超过24小时未支付，不享受立减！";
            }
        }
        return $ret;
    }
    // 订单列表和财务互通
    private function  _financePay($finance,$orderid){
        if(is_array($finance) && isset($finance[$orderid])){
            return  $finance[$orderid]['received'] > 0 ? 1 : 0;
        }
        return 0;
    }
    // 赠送保险
    public function action_ajaxFindFeel(){
        $lineOrderId = Filter::str('orderid');
        $detail = $this->jrequest('api/insurance/order/free/incomplete', self::ADD, ['lineOrderId' => $lineOrderId ],"insurance")->result();
        if($this->request->method() == Request::POST){
           $params = Filter::str('guestJson');
           $result = $this->jrequest('/api/insurance/order/free/guest/edit', self::ADD, ['guestJson' => $params],"insurance")->get();
           if($result['code'] == 200) {
               echo json_encode(['status' => 1]);
           }else{
               echo json_encode(['status' => 0]);;
           }
           die;
        }
        echo json_encode($detail?:[]);
        exit;
    }
    //付费保险
    public function action_insurancePay(){
        $this->_eventAnalysis('10033');
        $lineOrderId = Filter::str('orderid');
        $detail = $this->jrequest('api/insurance/order/quick/nopay', self::ADD, ['lineOrderId' => $lineOrderId ],"insurance")->result();
        echo json_encode($detail[0]);
        exit;
    }
    
    // 线下是否已付清
    public function _ispayed($finance,$orderid){
        if(is_array($finance) && isset($finance[$orderid])){
            return  $finance[$orderid]['received'] == $finance[$orderid]['receivable'] ? 1 : 0;
        }
        return 0;
    }
    // 导出
    private  function _export($data,$search){
        if(!empty($data['list']) && is_array($data['list']) ){
            $list['title'] = array( '序号','出团日期','回团日期','订单号','线路名称', '地接社','订单人数', '订单金额','下单人','下单时间', '支付状态','支付时间','支付','立减','订单状态' );
            if($search['ispay'] == 2){
                foreach ($data['list'] as $k => $v) {
                    $list['data'][$k] = [$k+1,date("Y-m-d",$v['gotime']),date("Y-m-d",$v['backtime']),$v['orderid'],$v['linetitle'],$v['selleropeartorcompanyname'],($v['adult']+$v['child']),$v['totalprice'],$v['buyername'],date("Y-m-d",$v['createtime']),$this->view['ispay'][$v['ispay']],'','','',$this->orderstatus[$v['state']]];
                }
            }else{
                foreach ($data['list'] as $k => $v) {
                    $paytime = $v['paytime'] ? date("Y-m-d",$v['paytime']) : '';
                    $list['data'][$k] = [$k+1,date("Y-m-d",$v['gotime']),date("Y-m-d",$v['backtime']),$v['orderid'],$v['linetitle'],$v['selleropeartorcompanyname'],($v['adult']+$v['child']),$v['totalprice'],$v['buyername'],date("Y-m-d",$v['createtime']),$this->view['ispay'][$v['ispay']], $paytime,$v['payamount'],$v['totalsubtract'],$this->orderstatus[$v['state']]];
                }
            }
            Common::exportExcel($list['title'], $list['data'], iconv('utf-8', 'gbk', $list['down_name']));
            die();
        }else{
            $this->showMessage('数据为空或导出超时！','');
            die();
        }
    }
    // 消息处理
    private function _searchMessage(&$params) {
        if ($params['mg'] >= 0) {
            $orderids = $this->jrequest('getOrder', self::GET, array('companyid' => $this->user['orderinfo']['companyid'], ' type' => $params['mg']), 'message')->result();  //根据category 读取订单
            if(is_array($orderids)){
                $params['orderids'] = "'" . implode("','", $orderids) . "'";
            }else{
                $params['orderids'] = "-1";
            }
        }
    }
    
    // 大社
    public function action_largetour(){
        $this->search = $search = array(
            'kw' => Filter::str('kw'), // 搜索关键字
            'ordernum' => Filter::str('ordernum'), // 订单编号
            'ispay' => Filter::int('ispay', -10), //支付状态
            'state' => Filter::int('state', -10), // 订单状态
            'ltype' => Filter::int('ltype', -1), // 线路类型
            'buyerid' => Filter::int('buyerid'), // 计调
            'createdate' => Filter::str('createdate'), // 下单时间段
            'godate' => Filter::str('godate'), // 出团时间段
            'configrmdate' => Filter::str('configrmdate'), // 确认时间段
            'paydate' => Filter::str('paydate'), // 支付时间段
            'ordersite' => Filter::int('ordersite'), // 来源
            'gname' => Filter::str('gname'), // 游客
            'mg' => Filter::int('mg', -1), //消息
            'companyids' => Filter::int('companyids', -10), // 大社标示
            'flag' => Filter::int('flag', 0), // 支付方式
            'companykw' => Filter::str('companykw'), // 供应商
            'pagesize' => Filter::int('pagesize', 20), // 每页条数
        );
        
        $this->_searchMessage($search);
        // 搜索处理
        $this->_search($search);
        // 处理大社营业部搜索
        $large = $this->_largeinit();
        if($search['companyids'] == -10){
            $search['companyids'] = $large['companyids'];
        }
        $params = array(
            'type' => 'list',
            'large' => 1,
            'companyid' => $this->user['companyinfo']['id'],
            'platforms' => 1,
            'search' => json_encode($search),
            'page' => Filter::int('p'),
            'pagesize' => Filter::int('ps', 20),
            'export' => Filter::int('export'), //导出列表标识
        );
        
   
        $data = $this->request('order/index', self::GET, $params, 'order')->result();
        // 列表处理
        $siteid = [];//站点id
        $this->_matchLine($data,$params['export'],$members,$siteid);

        // 保险处理
        $ids = array_unique(Arr::pluck($data['list'], 'id'));
        $ids = implode(',',$ids);
        $insurance = $this->jrequest('api/insurance/order/lineorder/select', self::ADD, ['lineOrderIds' => $ids],"insurance")->result();
        foreach ((array)$data['list'] as $k => $v) {
            //保险处理
            if($insurance){
                $aOrderList['list'][$k]['insurance'] =  $insurance[$v['id']] ?: "";
            }
        }
        //合同
        if($data['list']){
            $data['list'] = $this->_getContract($data['list']);
        }
        // 输出处理
        $this->page->total($data['total']);
        $this->view = array_merge($this->view,[
            'list' => (array) $data['list'],
            'orderstate' => $this->orderstatus,
            'ispay' => array('1'=>'已付','0'=> '未付','-1'=>"退款中",'-3'=>"已退款"),
            'members' => $members['result']['list'],
            'ltype' => $this->search['ltype'],
            'refundtype' => Kohana::$config->load('common.refundtype'),
            'color' => array('gray','cyan','orange','green','blue'),
            'companys' => $large['companys'],
            'totals' => $data['totals'],
        ]);
        //导出
        $params['export'] &&  $this->_export($data);
    }
    
    // 大社初始化
    private function _largeinit(){
        $companys = $this->jrequest('/api/company/groupall/'.$this->user['orderinfo']['companyid'], self::GET)->get();
        $this->_changer($companys);
        if($companys){  //Arr::pluck 使用此方法需保证 数组中有数据
            $companyids = implode(',',array_unique(Arr::pluck($companys, 'id')));
        }
        return ['companys' => $companys,'companyids' => $companyids];
    }

    // 退款
    public function action_refund(){
        $params = array(
            'orderid' => Filter::int('orderid'),
        );
        if($this->user['companyinfo']['companytype'] == 1){
            $result = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$this->user['companyinfo']['id'],'id'=>$params['orderid'],'large' => 1], 'order')->get();
        }else{
            $result = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$this->user['companyinfo']['id'],'id'=>$params['orderid']], 'order')->get();
        }
       
        if(intval($result['code']) !== 200){
            $this->showErrMessage($result['code'], $result['message']);
        }
        $result = $result['result'];
        $list = $this->request('order/refund',self::GET, ['orderid'=>$params['orderid']], 'order')->get();
        if(intval($list['code']) !== 200){
            $this->showErrMessage($list['code'], $list['message']);
        }
        $list = $list['result'];
        $insurance = $this->jrequest('api/insurance/order/lineorder/select',self::ADD,['lineOrderIds'=>$params['orderid']],'insurance')->get();
        if(intval($insurance['code']) !== 200){
            $this->showErrMessage($insurance['code'], $insurance['message']);
        }
        $this->view =array(
            'detail' => $result['detail'],
            'list' => $list['list'],
            'refundamount'=>$list['refundamount'],
            'insurance'=>$insurance['result'][$params['orderid']]['freeStatus'],
            'insurancePrice'=>$insurance['result'][$params['orderid']]['totalPrice'],
            'productPrice'=>$insurance['result'][$params['orderid']]['freeStatus']==1?$insurance['result'][$params['orderid']]['productPrice']:0,
            //'ret' => orderPay::payDiscount($result['detail']),
	    'ret' =>array('discount'=>round(($result['detail']['totalprice']-$result['detail']['payamount'])*100/$result['detail']['totalprice']),2),
	);
    }
    
    // 退款
    public function action_ajaxrefund(){
        $params = array(
            'orderid' => Filter::int('orderid'),
            'refundamount' => Filter::int('refundstyle')==1?-1*Filter::float('refundamount'):-1*Filter::float('refundmoeny'),
            'refundtype' => Filter::int('refundtype'),
            'detail' => Filter::str('detail'),
            'username' => $this->user['memberinfo']['realname'],
            'refundstyle' => Filter::int('refundstyle'),
        );
        
        if($this->user['companyinfo']['companytype'] == 1){
            $result = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$this->user['companyinfo']['id'],'id'=>$params['orderid'],'large' => 1], 'order')->get();
        }else{
            $result = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$this->user['companyinfo']['id'],'id'=>$params['orderid']], 'order')->get();
        }  
        
        if(intval($result['code']) !== 200){
            $this->showErrMessage($result['code'], $result['message']);
        }
        $result = $result['result'];
        if($result['detail']['payamount'] < abs($params['refundamount'])){
            $result = array(
                'status' => 0,
                'msg'=> '退款金额不能大于支付金额',
            );
            echo json_encode($result);
            exit;
        }
//        if(abs($result['detail']['refundamount'])>0 && abs($result['detail']['refundamount']) != abs($params['refundamount'])){
//                $result = array(
//                    'status' => 2,
//                    'msg'=> '卖家设置金额变化，请刷新页面后再次确认',
//                );
//                echo json_encode($result);
//                exit;
//            }
        $sellercompanyid = $result['detail']['sellercompanyid'];
        if ($this->request->method() == Request::POST) {
            $log = true;
            if($params['refundtype']==1){
                $param = array('orderid'=>$params['orderid'],'memberid'=>$this->user['memberinfo']['id'],'detail'=>$params['detail'],'readmemberid'=>$params['selleropeartorid']);
                $log = $this->request('order/log',self::ADD, ['type'=>1,'params'=>$param], 'order')->get();
                if(intval($log['code']) !== 200){
                    $this->showErrMessage($log['code'], $log['message']);
                }
                $log = $log['result'];
            }
            $result = $this->request('order/refund',self::UPDATE, $params, 'order')->get();
            if(intval($result['code']) !== 200){
                $this->showErrMessage($result['code'], $result['message']);
            }
            $result = $result['result'];
            $result =array(
                'status' => $result&&$log ? 1 : 0,
                'msg'=>$result&&$log ? '申请成功' : '申请失败',
             );
            if($result['status']==1){
                $paramrefund = array(
                    'companyid' => $sellercompanyid,
                    'orderid' => $params['orderid'],
                    'type' => 16,
                    'msg' => '退款订单',
                );
                Restful::Async(true);
                $this->addOrderMsg($paramrefund);
            }
            echo json_encode($result);
            exit;
        }
    }
    
    // 订单支付(旅付宝)
    public function action_orderPay () {
        $this->_check(11107);
        $paytype = Filter::int('paytype');
        if ($this->request->method() == Request::POST ){
            $this->params = array(
                'orderid' => implode(',',Filter::intarr("orderid")), //订单id
                'paystyle'=> 4,
                'platform' => $this->platform,
            );
            $ordersResult = $this->request('order',self::GET,['type'=>'otherSearch','orderids'=>$this->params['orderid']], 'order')->get();//订单结果
            if(intval($ordersResult['code']) !== 200){
                $this->showErrMessage($ordersResult['code'], $ordersResult['message']);
            }
            $ordersResult = $ordersResult['result'];
            $to2List = [];
            $sellcompanyid = [];
            foreach($ordersResult['list'] as $value){
               $to2List[] = (int)$value['to2to'];
               $sellcompanyid[] = (int)$value['sellercompanyid'];
            }
            $to2List = array_unique($to2List);
            $sellcompanyid = array_unique($sellcompanyid);
            if(count($to2List) != 1){
                $this->showMessage('暂可支持同一家供应商订单批量支付，请重新选择！','order.html',$alert = false);
            }
            if(count($to2List) == 1 && $to2List[0] == 2){
                if(count($sellcompanyid) != 1){
                $this->showMessage('暂可支持同一家供应商订单批量支付，请重新选择！','order.html',$alert = false);
                }
            }
            
        }else{
            $this->params = array(
                'orderid' => Filter::int("orderid"), //订单id
                'paystyle'=> 4,
                'platform' => $this->platform,
            );
        }
        if($this->user['companyinfo']['companytype'] == 1){
            $this->params['largetour'] = 1;
        }  
        if($paytype == 5){
            $this->params['paytype'] = 5;
        }
        $apiUrl = 'http://'.$this->sitedomain['pay'][$this->platform]."/buyer?param=".base64_encode(json_encode($this->params));
        header("Location: ". $apiUrl ."");exit;
    }
    
    public function action_details(){
        $id = Filter::int('id'); //订单id
        $file = Filter::str('file'); //图片下载
        if($file){
            //文件的类型 
            header('Content-type: application/pdf'); 
            //下载显示的名字 
            header('Content-Disposition: attachment; filename='.$file); 
            readfile("$file"); 
            exit(); 
        }
        $aorder = $this->getOrder($id, 'order/index');//订单的基本信息
        empty($aorder) && Common::windowClose('您访问的数据不存在');
        $aline = $this->getLine($aorder['lineid']);
        $aline['gotraffic'] = Common::dealSellerTract($aline['gotraffic']); //交通的处理
        $aline['backtraffic'] = Common::dealSellerTract($aline['backtraffic']);
        //修改订单消息状态服务
        $aMessage = array(
            'companyid' => $this->user['orderinfo']['companyid'],
            'orderid' => $id,
        );
        $this->delOrderMsg($aMessage);
        //取订单状态中文名称
        if($aorder['ispay'] ==1){
            $aorder['stateName'] = '已支付';
        }else{
            $orderstatus = [0 => '未确认', 1 => '待支付', 2 => '取消', 3 => '名单不全', 4 => '已出票']; //订单状态
            $aorder['stateName'] = $orderstatus[$aorder['state']];
        }
        //取目的地的名称
        $aline['departureName'] = json_decode($aline['departure'],true)['city']?:json_decode($aline['departure'],true)['province'];
        $fpinfo = $this->jrequest('api/invoice/queryCompanyInvoiceAccount/' . "{$this->user['companyinfo']['id']}", self::GET)->result();//开票信息
        $guests = $this->getGuest($id); //游客名单服务
        Data::_setOrderDetailshow($aorder, $guests, $aline); //展示信息处理
        if($aorder['isinvoice']){
            $fpinfo = $this->jrequest('/api/invoices/invoiceInfo', self::ADD, ['orderId' => $id],'invoice')->result();//开票信息
        }
        //计算出房间数
        if($aorder['totalsingleroom']>0 && $aorder['totalsingleroom']/$aorder['singleroom']>0){
            $aorder['roomSum'] =ceil((($aorder['totalsingleroom']/$aorder['singleroom'])+$aorder['adult'])/2);
        }else{
            $aorder['roomSum'] = ceil($aorder['adult']/2);
        }
        //目的地取客服
        if ($aline['companyid'] && $aline['cityids']) {
                $city = json_decode($aline['cityids'], 1);
                $city[0]['city'] && $city = $city[0];
                $city[0]['province'] && $city = $city[0];
                if ($lineinfo['linecategory'] == 2) {
                    $aline['cityid'] = $city['city'] ? $city['city'] : $city['province'];
                } else {
                    $aline['cityid'] = $city['province'] ? $city['province'] : $city['city'];
                }
            $customerinfo = $this->request('system/callcenter', self::GET, array('companyid' => $aline['companyid'], 'cityids' => $aline['cityid'], 'siteid' => $aline['siteid'], 'type' => 'findcallcenter',),'base')->get();
            if ($customerinfo['code'] !== 200) {
                $this->setErrToHeader($customerinfo);
            }
        }
        //红包
        $hongbaoparams = array(
            'type' => 1, //必填用来选择服务
            'companyid' => $this->user['orderinfo']['companyid'], //必填自己的公司ID
            'sellercompanyid' => $aline['companyid'],
        );
        $hongbao = $this->request('hongbao/buyerfinance', self::GET, $hongbaoparams, 'hongbao')->result();
        //接送
        $aorder['takeid'] = $aorder['tianchi']?:$aorder['takeid'];
        $number  = $aorder['adult']+$aorder['child'];
        $takeinfo = $aorder['takeid'] ? $this->getTakeOne($aorder['takeid'],$aorder['tianchi'],$aorder['takeprice'],$aline['lineid'],$number) : array('detail' => []);
        if(((int)$takeinfo['detail']['tag']!= (int)$aorder['taketype'] || (int)$aorder['takeprice'] != (int)$takeinfo['detail']['tianchiprice']) && $aorder['tianchi']){
            $newtake = 1;
        }
        $takes = $this->getTake(array('lineid'=> $aorder['lineid'], 'taketype'=> -1),$aline['tianchi'],$aline['lineid']);
        //发票状态
        $invoiceInfo = Controller_Admin_Orderservice::getbuyerinvoinfo(array('companyid'=> $this->user['companyinfo']['id']),$this);
        $invoice = [];
        if($invoiceInfo){
            $invoiceInfo['status'] == 0 ? $invoice = ['status'=>0,'msg1'=>'您的开票资质正在审核中。','msg2'=>'查看申请>>'] : ($invoiceInfo['status'] == 2 ? $invoice = ['status'=>2,'msg1'=>'您的开票资质未通过审核。','msg2'=>'重新申请开票>>'] : $invoice = ['status'=>1,'info'=>$invoiceInfo]);
        }else{
            $invoice = ['status'=>'','msg1'=>'您还未申请开通平台发票。','msg2'=>'立即申请开票>>'];
        }
        //是否有保险,有取保险信息
        $checkInsurance = $this->checkLineFreeInsurance(array('webSite' =>$this->websitevalue[$this->platform] ,'siteId' => $aline['siteid'], 'lineCategoey' => $aline['linecategory']));
        $freeInsurance = [];
        if($checkInsurance){
            $freeInsurance = $this->lineFreeInsurance(array('webSite' =>$this->websitevalue[$this->platform] ,'siteId' => $aline['siteid'], 'lineCategoey' => $aline['linecategory'], 'days' => $aline['days']));
        }
        //查看订单是否已经预定保险，是取购买保险的信息
        $insurance = $this->jrequest('api/insurance/order/lineorder/select', self::ADD, ['lineOrderIds' => $id],"insurance")->result();
        if($insurance[$id]['freeStatus']==1 && $insurance){
            $isInsurancePay = $this->jrequest('api/insurance/order/free/incomplete', self::ADD, ['lineOrderId' => $id ],"insurance")->result();//查询有保险情况下是否能支付
            $insuranceInfo = $this->jrequest('api/insurance/listpage/show/' . $insurance[$id]['mainProductId'], self::GET, array(), 'insurance')->result();
            is_array($insuranceInfo) && $insuranceInfo['amt'] = $insurance['freeStatus'] == 1 ?0:$insurance[$id]['totalPrice'];
            is_array($insuranceInfo) && $insuranceInfo['productPrice'] =$insurance[$id]['productPrice'];
        }
        //立减和立减倒计时
        $nowtime = time(); 
        $subtract= 0;
        $lijian = $this->request('order/pay', self::GET, ['webSite' => $aorder['ordersite'], 'siteId' => $aorder['siteid'], 'sellerId' => $aorder['sellercompanyid'], 'buyerId' => $aorder['buyercompanyid'], 'type' => "getDiscount"], 'order')->result();
        $sellerHour = ($aorder['confirmtime'] +$lijian['sellerHour']*60*60)-$nowtime;
        $platformHour = ($aorder['confirmtime'] +$lijian['platformHour']*60*60) -$nowtime;
        $lineHour = $aorder['confirmtime'] + 86400 -$nowtime ;
        if($sellerHour >0){
            $subtract +=  $lijian['sellerPercent']+$lijian['platformAdditionalPercent'];
            $lijiantime[] = $sellerHour;
        }
        if($platformHour>0){
            $subtract +=  $lijian['platformPercent'];
            $lijiantime[] = $platformHour;
        }
        if($lineHour>0 && $aline['platformdiscount']>0){
            $subtract += $aline['platformdiscount'];
            $lijiantime[] = $lineHour;
        }
        if($lineHour>0 && $aline['discountamount']>0){
            $peopleSum = ($aorder['adult']+$aorder['child']+$aorder['baby'])*$aline['discountamount'];
            $lijiantime[] = $lineHour;
        }
        $lijiantime &&  asort($lijiantime);
        if(($subtract>0||$peopleSum>0) && $lijiantime){
            $lijiantime = current($lijiantime);
            $ret = [];
            $ret['tipico'] = "&#xe8c8;";
            if(($aorder['gotime']+86399)>$nowtime && $lijiantime>0 && $aorder['confirmtime']<$aorder['gotime']){
                $aorder['platformdiscount'] = $subtract;
                $aorder['countdown'] = $lijiantime;
                $aorder['peopleSum'] = $peopleSum;
            }
        }
        if($aorder['refundtype']){
            $refund = $this->request('order/refund',self::GET, ['orderid'=>$id], 'order')->result();
            $refund = $refund['list'];
        }
        $this->_orderEdit($aorder, $aline, $guests); //订单详情提交的操作
        $this->view =[
            'aorder'=>$aorder,
            'aline'=>$aline,
            'destplace' => strpos($aline['destplace'],'日本') === false ? 0 : 1,
            'sellerinfo' => $this->getSellerinfo($aorder['sellercompanyid']), //供应商标题与联系方式服务
            'company' => $this->sellCompanyOpt($aline),
            'pricelist' => $this->getPriceList($id),//订单价格明细列表
            'guests' => $guests, //游客名单
            'loglist' => $this->getLog($id), //订单日志服务
            'fpinfo'=>$fpinfo,//发票信息
            'customerinfo' => $customerinfo['result'],
            'hongbao' => (array) $hongbao['list'],
            'checkInsurance' => $checkInsurance,
            'freeInsurance' => $freeInsurance,
            'insuranceInfo'=>$insuranceInfo,
            'isInsurancePay'=>$isInsurancePay,
            'refund'=>$refund?:[],
            'newtake'=>$newtake,
            'cardcarr' =>  Kohana::$config->load('common.cardcategory'), //证件类型
            'invoiceCheck' => $this->checkOrderInvoice(array('orderid' => $id)),//发票地址check
            'other' => [
                'takeinfo' => (array)$takeinfo['detail'],
                'takes' => (array)$takes['list'], 
                'invoice' => $invoice, //发票状态
                'hyinvoiceaddr' => Controller_Admin_Orderservice::getinvoaddr(array('companyid'=> $this->user['companyinfo']['id']),$this), //鸿运发票地址
            ]
        ];
    }
    
    //导出游客名单
    public function action_exportGuest() {
        $guests = $this->getGuest(Filter::int('id')); //游客名单服务
        $cardcategory = Kohana::$config->load('common.cardcategory');
        $sexarr = Kohana::$config->load('common.sexarr');
        $typearr = Kohana::$config->load('common.typearr');
        
        $arrTemp = array();
        foreach ((array)$guests as $key => $value) {
            $arrTemp[$key]['num'] = $key + 1;
            $arrTemp[$key]['title'] = $value['title'];
            $arrTemp[$key]['gender'] = $sexarr[$value['gender']];
            $arrTemp[$key]['category'] = $typearr[$value['category']];
            $arrTemp[$key]['mobile'] = $value['mobile'];
            $arrTemp[$key]['cardcategory'] = $cardcategory[$value['cardcategory']];
            $arrTemp[$key]['idcard'] = $value['idcard'];
            $arrTemp[$key]['detail'] = $value['detail'];
        }
        $title = array('序号', '姓名', '性别', '类型', '手机号码', '证件类型', '证件号','备注');
        //下列数据 $this->use 获得
        Common::exportExcel($title, $arrTemp, "游客名单" . date('YmdHis'));
    }

    
    //收藏商家
    public function action_ajaxFavouriteCompany() {
        if (!$this->user) {
            echo'请登陆后再执行本次操作';
            die;
        }
        
        $params = array(
            'category' => 1, //接口类型 创建收藏商家
            'sellerlineid' => Filter::str('id'), //商家id
            'memberid' => $this->user['memberinfo']['id'], //用户id
            'title' => Filter::str('title'), //公司标题
            'detail' => Filter::str('content'), //备注信息
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

    //订单取消
    public function action_cancel() {
        $this->_check(11103);
        $result = 0;
        $orderid = Filter::int('id'); //订单id
        $detail = Filter::str('detail'); //取消原因
        //订单信息服务
        $aorder = $this->getOrder($orderid, 'order/index');
        if (empty($aorder)) {
            echo $result;
            exit;
        }
        $params = array(
            'type' => 'cancel',
            'id' => $orderid,
            'detail' => $detail,
            'platforms' => 1,
        );

        $code = $this->request('order/state', self::UPDATE, $params, 'order')->get();

        if ($code['code'] == 200) {
            $result = 1;
            Restful::Async(true); //开启异步
            $this->request('/api/insurance/order/free/cancel', self::ADD, ['lineOrderId'=>$orderid,'memberId'=>$this->user['memberinfo']['id']], 'insurance')->get();//免费保险
            $this->request('/api/take/new/orders/cancel', self::ADD, ['companyId'=>$this->user['companyinfo']['id'],'lineOrderId'=>$orderid,'lineOrderCode'=>$aorder['orderid']], 'fleet')->get();//专车订单取消
            //订单操作日志 
            $param = array(
                'orderid' => $orderid,
                'memberid' => $this->user['orderinfo']['id'],
                'detail' => sprintf(Kohana::$config->load('tip.order.cancel'), $aorder['orderid'], $this->orderstatus[$aorder['state']], '已取消'),
                'readmemberid' => $aorder['selleropeartorid'],
            );
            $this->_addOrderLog($param);
            //积分日志
            $this->_cancelIntegLog($aorder);
            //消息
            $aMessage = array(
                'companyid' => $aorder['sellercompanyid'],
                'orderid' => $orderid,
            );
            $this->delOrderMsg($aMessage);
            $params = array(
                'companyid' => $aorder['sellercompanyid'],
                'orderid' => $orderid,
                'type' => 2,
                'msg' => '订单取消',
            );
            $this->addOrderMsg($params);
             //app推送
            $apparams = array(
                'companyid' => $aorder['sellercompanyid'],
                'orderid' => $orderid,
                'msg' => '订单取消',
            );
            $this->addAppMsg($apparams);
            //pc推送
            $pcparams = array(
                'pusherparentid' => $this->user['orderinfo']['companyid'],
                'pusherid' => $this->user['orderinfo']['id'],
                'receiverparentid' => $aorder['sellercompanyid'],
                'receiverid' => $aorder['selleropeartorid'],
                'msg' => '取消订单',
                'optype' => 2,
                'url' => $code['result']['url'],
            );
            $this->addPcMsg($pcparams);
        } 
        echo $result;exit;
    }

    //订单恢复
    public function action_recover() {
        $this->_check(11108);
        $result = 0;
        $orderid = Filter::int('id'); //订单id
        $dateid = Filter::int('dateid'); //团期id
        //订单信息服务
        $aorder = $this->getOrder($orderid, 'order/index');
        //线路服务
        $aline = $this->getLine($dateid);
        if ($aline) {
            // $aline['surplus'] = $aline['planid'] ? $aline['leaveseats'] : ($aline['person'] - $aline['personorder']);
        }
        Common::lineState($aline);
        if ($aline['deletetime'] > 0) {
            echo (501); // 卖家删除订单买家不可恢复.
            exit;
        }
        if ($aline) {//订单可否恢复的判断
            if ($aline['isstop'] == 0 || ( $aline['isstop'] == 2 && $aline['surplus'] == 0)) {
                $params = array(
                    'type' => 'recover',
                    'id' => $orderid,
                    'lineinfo' => json_encode($aline),
                );

                $code = $this->request('order/state', self::UPDATE, $params, 'order')->get();
                if ($code['code'] == 200) {
                    $result = 200;
                    Restful::Async(true); //开启异步
                    //订单日志
                    $param = array(
                        'orderid' => $orderid,
                        'memberid' => $this->user['orderinfo']['id'],
                        'detail' => "您的订单[{$aorder['orderid']}]已恢复",
                        'readmemberid' => ($aorder['selleropeartorid'] ? $aorder['selleropeartorid'] : $aline['memberid'] ),
                    );
                    $this->_addOrderLog($param);
                    //积分日志
                    $this->_recoverIntegLog($aorder);
                }
            } else{
                $result = $aline['isstop']; //其他状态 1 2 3 4 5
            }
        }
        echo $result;
        exit;
    }

    //ajax取消操作
    public function action_ajaxCancelOrder() {
        $this->_check(11105);
        $orderid = Filter::int('orderid');
        //订单信息服务
        $canmodel = $this->getOrder($orderid, 'order/index');
        $result = array('res' => 0);
        if ($canmodel) {
            $result['status'] = $canmodel['state'];
            $result['clearmode'] = nl2br($canmodel['cancledetail']);
            $result['res'] = 1;
        }
//        $this->getCoins($this->user['memberinfo']['id'],121,$this->user['memberinfo']['username']);//挖金币调用
        echo json_encode($result);exit;
    }

    // ajax线路评价
    public function action_ajaxAppraise() {
        $this->_check(11104);
        if ($this->request->method() != Request::POST) {
            return;
        }

        $json = json_decode(Filter::str('data'), true);
        $params = array(
            'orderid' => $json['orderid'],
            'buyercompanyid' => $json['buyercompanyid'],
            'sellercompanyid' => $json['sellercompanyid'],
            'memberid' => $json['memberid'],
            'detail' => $json['detail'],
            'companyappraise' => $json['companyappraise'],
            'lineappraise' => $json['lineappraise'],
            'appraise' => implode(',', $json['appraise']),
            'createtime' => time(),
        );
        $line = $this->request('line/date', self::GET, ['appraise' =>1,'dateid'=>$json['lineid']], 'line')->get();
        $this->_changer($line);
        $params['lineid'] =$line['detail']['lineid'];
        if(is_array($json['photo[]'])){
            $params['photo'] =  implode(',', $json['photo[]']);  
        }else {
            $params['photo'] = $json['photo[]'];
        }
        $data = $this->request('order/appraise', self::ADD, $params, 'order')->get();
        $this->_changer($data);
        // 获取订单详情
        $integral = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$params['buyercompanyid'],'id'=>$params['orderid']], 'order')->get();
        $this->_changer($integral);
        $buyerapp = $this->request('order/index', self::UPDATE, ['type'=>'appraise','companyid'=>$integral['detail']['buyercompanyid'],'appraise'=>1,'id'=>$integral['detail']['id'],'isblance'=>1], 'order')->result();
        if($integral['detail']['totalintegral'] > 0){
            $this->_integral($integral);
        }
        $result = array(
            'status' => $data ? 1 : 0,
            'integral' => $integral['detail']['totalintegral'],
	    'params' => $params,
	    'data' => $data,
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 评价积分操作
    private function _integral($integral){
        $sellerintegral = array(
            'aIntegral' =>array(
                ':companyid' => $integral['detail']['sellercompanyid'],
                ':memberid' => $integral['detail']['selleropeartorid'],
                ':action' => 11,
                ':integral' => $integral['detail']['totalintegral'],
                ':detail' => "组团社在".date("Y-m-d H:i:s",time())."领取地接社{$integral['detail']['totalintegral']}积分 订单编号：{$integral['detail']['orderid']}",
                ':lineid' => $integral['detail']['lineid'],
                ':orderid' => $integral['detail']['id'],
                ':category' => 0,
            ),
        );
        
        $seller  = $this->request('integral/sellintegral', self::ADD, $sellerintegral, 'integral')->get();
        $this->_changer($seller);
        $buyerintegral = array(
            'type' => 21,
            'aIntegral' =>  array(
                ':companyid' => $integral['detail']['buyercompanyid'],
                ':memberid' => $integral['detail']['buyerid'],
                ':action' => 21,
                ':integral' => $integral['detail']['totalintegral'],
                ':detail' => "组团社在".date("Y-m-d H:i:s",time())."领取地接社{$integral['detail']['totalintegral']}积分 订单编号：{$integral['detail']['orderid']}",
                ':lineid' => $integral['detail']['lineid'],
                ':orderid' => $integral['detail']['id'],
                ':category' => 0,
                 ),
        );
        $buyer = $this->request('integral/index', self::ADD, $buyerintegral, 'integral')->get();
        $this->_changer($buyer);
    }

    // ajax线路评价
    public function action_ajaxFind() {
        if ($this->request->method() != Request::POST) {
            return;
        }
        $params = array(
            'orderid' => Filter::int('orderid'),
        );
        $data = $this->request('order/appraise', self::GET, $params, 'order')->result();
        $appraise = array('不满意', '一般', '满意');
        $result = array(
            'status' => isset($data['id']) ? 1 : 0,
            'data' => explode(',', $data['appraise'] . "," . $data['companyappraise']),
            'photo' => explode(',', $data['photo']),
            'detail' => $data['detail'],
            'answer' => $data['answer'],
            'line' => $appraise[$data['lineappraise']],
            'imghost'=> Kohana::$config->load('site.params.host.images'),
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    //弹出红包页面
    public function action_hongbao() {
        $params = array(
            'type' => 1, //必填用来选择服务
            'companyid' => $this->user['orderinfo']['companyid'], //必填自己的公司ID
            'sellercompanyid' => Filter::int('companyid'),
        );
        $result = $this->request('hongbao/buyerfinance', self::GET, $params, 'hongbao')->result();
        $this->view['hongbao'] = (array) $result['list'];
    }

    //获得接送信息
    public function action_ajaxTake() {
        $category = Filter::int('category');
        $dateid = Filter::int('dateid');
        $result = $this->request('line/buyerfee', self::GET, array('type' => 'date', 'dateid' => $dateid, 'tag' => $category), 'line')->result();
        echo json_encode((array) $result['list']);
        exit;
    }
    
    
    //添加发票地址
    public function action_ajaxinvoiceaddr(){
        $params = array(
            'companyid' => $this->user['companyinfo']['id'],
            'memberid' => $this->user['memberinfo']['id'],
            'name' => Filter::str('name'),
            'mobile' => Filter::str('mobile'),
            'address' => Filter::str('address'),
        );
        $result = $this->getCompanySealPath();
		
        if(!isset($result['sealpath']) || empty($result['sealpath'])){
            echo json_encode(['status' => '2' , 'val' => "" ]);exit;    
        }else{
            $res = $this->addInvoiceAddr($params);
            echo json_encode(['status' => ($res ? 1: 0) , 'val' => ($res ? : '创建地址失败') ]);exit;    
        }
    }
    
    //检查是否上传公章
    public function action_ajaxCheckSeal(){
        $res = 0;
        $result = $this->getCompanySealPath();
        if(isset($result['sealpath']) && $result['sealpath']){
            $res = 1;
        }
        echo $res;exit;
    }
    

    // 订单详情修改
    private function _orderEdit($order, $line, $guests) {
        if ($this->request->method() == Request::POST) {
            $orderid = $order['id'];
            list($otherinfo, $guestinfo) = Data::_orderEditArray(); //表单提交的参数
            if(Filter::int('tianchi') && $otherinfo['takeid']){
                $otherinfo['takeprice'] = $this->newTianChiAmt($otherinfo['takeid'],$line['lineid']);
            }elseif($otherinfo['takeid']){
                $aTake = $this->getTakeOne($otherinfo['takeid']);
                $otherinfo['takeprice'] = $aTake['detail']['price'];
            }
            $invoiceinfo = Data::_bookPostInvoice($this->user, []);//发票
            //判断页面是否被修改过
            if($this->checkOrderMofiy($order, $otherinfo)){
                $this->showMessage('订单已被修改,请重新操作！', $_SERVER['REQUEST_URI']);
            }
			
            //并发
            $this->checkRedisOrder($line);
            $otherinfo['instantconfirm'] = $order['instantconfirm'];
            $delinfo = $this->_dealGuest($guests, $guestinfo); //删除信息

            $code = $this->editOrder('order/index', $orderid, $line, $otherinfo, $guestinfo, $delinfo, $invoiceinfo); //修改服务调用
            if ($code['code'] == 200) {
                $aordernew = $this->getOrder($orderid, 'order/index'); //新订单信息
                $guestsnew = $this->getGuest($orderid); //游客信息

                //如果修改了供应商计调，则查出名字/目的是为了写日志
                if($aordernew['selleropeartorid'] && $order['selleropeartorid'] != $aordernew['selleropeartorid']){
                    $result = $this->jrequest("api/member/" .$aordernew['selleropeartorid'].','.$order['selleropeartorid'], self::GET, [], 'member')->result();
                    if(!empty($result)){
                        $result = Common::arr2ToArr($result,'realname','id');
                        $aordernew['selleropeartorrealname'] = $result[$aordernew['selleropeartorid']];
                        $order['selleropeartorrealname'] = $result[$order['selleropeartorid']];
                    }
                }

                Restful::Async(true); //开启异步
                list($aOldOrderData, $aNewOrderData) = $this->_dealOrderData($order, $aordernew, $guests, $guestsnew);
                //积分服务
                $this->_editIntegralLog($orderid, $aordernew, $order);
                //创建免费保险
                Filter::int('checkinsurance') ? $this->createFreeInsuranOrder($order, $line, Filter::int('checkinsurance'),1) : '' ;
                //订单日志服务
                $this->_editOrderLog($aNewOrderData, $aOldOrderData, ($order['selleropeartorid'] ? $order['selleropeartorid'] : $line['memberid']));
                //订单消息服务
                $aMessage = array(
                    'companyid' => $line['companyid'],
                    'orderid' => $orderid,
                );
             //   $this->delOrderMsg($aMessage);
//                $mparams = array(
//                    'companyid' => $line['companyid'],
//                    'orderid' => $orderid,
//                    'type' => 5,
//                    'msg' => '订单修改',
//                );
//                $this->addOrderMsg($mparams);
                $this->addOrderConfirmFinanceLog($order);
                if($code['result'] == 1 && $otherinfo['instantconfirm']==1 && !Filter::int('checkinsurance') && $order['ispay'] != 1){
                    $param = array(
                        'orderid' => $orderid,//批量支付时，多个id用，分隔
                        'paystyle' => 4,//支付类型 1=积分订单
                        'website' => $this->websitevalue[$this->platform],//平台1-cy 2-ht， 4-mc
                        'memberid' => $this->user['memberinfo']['id'],//买家Id,
                        'companyid' => $this->user['companyinfo']['id'],//公司id
                        'platform' => $this->platform,//平台
                    );
                    $apiUrl = 'http://' . $this->sitedomain['pay'][$this->platform] . "/buyer?param=" . base64_encode(json_encode($param));
                    header("Location: " . $apiUrl);
                }else{
                    $url = "/order.html";
                    $this->showMessage('操作成功！', $url);
                }
            } else {
                $this->showErrMessage($code['code'], '操作失败！', $_SERVER['REQUEST_URI']);
            }
        }
    }
    // 获取代金券列表
    public function action_ajaxCoupon() {
        $params = array(
          'orderid' => Filter::int('id'),
          'buyerid' => $this->user['memberinfo']['id'],  
          'companyid' => $this->user['companyinfo']['id'],
        );
        $order = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$params['companyid'],'id'=>$params['orderid']], 'order')->result();
        if($order['detail']){
        $detail = $order['detail'];
        // 获取规则
        $lineparams = array(
                    'dateid' => $detail['lineid'],
                    'type' => 'dateDetail'
                );
        $line = $this->request('line/index', self::GET, $lineparams, 'line')->result();
        $act = $this->jrequest('api/promotion/seller/activity/line/'.$detail['sellercompanyid'].'/'.$line['detail']['id'].'/'.$detail['ordersite'].'/'.$detail['siteid'],self::GET,'','promotion')->get(); 
        if($act['code'] == 200 && is_array($act['result'])){
           $percent['percent'] = $act['result']['useQuotaPercent'];
           $percent['id'] =  $act['result']['activityId'];
        }else{
            $percent = $this->request('coupon', self::GET, ['type'=>'use','siteid'=>$detail['siteid'],'ordersite'=>$detail['ordersite']], 'base')->result();
        }
        $percent && $most = round($detail['totalprice']*$percent['percent']*0.01) ;
        }else{
            echo json_encode(['status' => 2]);
            exit(); 
        }
        //暑期活动
        $actids = Kohana::$config->load("holiday.actids");
        if($percent['id'] && in_array($percent['id'], $actids)){
            $act = 1;
        }else{
            $act = 0;
        }
        if($percent['id'] && in_array($percent['id'], $actids) && $detail['totalprice'] >= 100){           
            $sum = intval($detail['totalprice']/100);
        }else{
            $sum = 0;
        }
        $tmp = $this->request('coupon', self::GET, ['type'=>'getCoupon','amount'=>$most,'memberid'=>$params['buyerid'],'isact'=>$act,'actsum'=>$sum], 'base')->result();
//        ---------新年活动-----
        if(time() > strtotime('2017-01-01 00:00:01') && time < strtotime('2018-01-12 23:59:59')){
            $discountInfo = $this->request('order/pay',self::GET,['webSite'=>$detail['ordersite'],'siteId'=>$detail['siteid'],'sellerId'=>$detail['sellercompanyid'],'buyerId'=>$detail['buyercompanyid'],'type'=>'getDiscount'], 'order')->result();
            foreach((array)$tmp['list'] as $key => $value){
                if(($discountInfo['sellerPercent'] != 2 && $line['detail']['platformdiscount'] != 2 && $value['getactivityid'] =='20180101') || ($detail['sellerid'] && $value['getactivityid'] =='20180101')){
                    unset($tmp['list'][$key]);
                }
            }
        }
        if(!$tmp['list']){
            echo json_encode(['list' => null]);die;
        }
//        ---------end----------
        if($this->request->method() == Request::POST){
            //防止支付过之后不刷新页面再使用券
            $itime = time() - $detail['paystarttime'];
            if($itime < 3600 || $detail['ispay'] == 1) {
                $this->showMessage("订单正在支付中","order.html"); //支付中
            }
            $ids = Filter::intArr('ids');
            !$ids &&  $this->showMessage("请选择代金券","order.html");
            $ids= implode(',',$ids);
            $result = $this->request('coupon', self::UPDATE, ['type'=>'freeze','ids'=>$ids,'orderid'=>$params['orderid'],'couponamount'=>$most ,'useactivityid'=>$percent['id']], 'base')->result();//冻结代金券
            if($result){
                $this->showMessage("选择成功","order.html");
            }  else {
                $this->showMessage("失败","order.html");
            }
        }
        $result = array(
            'most' => $most,// > 10 ? sprintf("%.2f", $most) : 10,
            'num' => $tmp['total']['total'],
            'all' => $tmp['total']['totalcoupon'],
            'lastdate' => $tmp['total']['endtimes'],
            'list' => $tmp['list'],
        );
        echo json_encode($result);
        exit();        
    }
    
    // ajax取消操作代金券
    public function action_cancelCoupon() {
        $params = array(
          'orderid' => Filter::int('orderid'),
          'buyerid' => $this->user['memberinfo']['id'],  
          'companyid' => $this->user['companyinfo']['id'],
        );
        $order = $this->request('order', self::GET, ['type'=>'detail','companyid'=>$params['companyid'],'id'=>$params['orderid']], 'order')->result();
        // 支付锁定
        $itime = time() - $order['detail']['paystarttime'];
        if($itime < 3600*24 || $order['detail']['ispay'] == 1) {
            $this->showMessage("订单正在支付中","order.html"); //支付中
        }
        //订单信息服务
        $result = $this->request('coupon', self::UPDATE, ['type'=>'reset','useorderid'=>$params['orderid']], 'base')->result();
        if($result == 1){
            $this->showMessage("取消成功","order.html");
        }  else {
            $this->showMessage("取消失败","order.html");
        }
    }
       
    public function after() {
        parent::after();
    }
    
    // 我的线路列表
    public function action_myLine() {
        $search = $this->_searchMyLine();// 搜索处理
        $params = [
            'type' => 'list',
            'platforms' => 1,
            'companyid' => $this->user['companyinfo']['id'],
            'search' => $search,
            'page' => Filter::int('p'),
            'pagesize' => Filter::int('ps',20),
        ];
        if (!in_array(11801, json_decode($this->roles['roleids']))) {   //判断用户是否拥有查看所有订单权限
            $params['memberid'] = $this->user['memberinfo']['id'];
        }
        $result = $this->request('order/buyer', self::GET, $params, 'order')->result();//订单列表
//        $data = $this->request('order/buyer', self::GET, array_merge($params, ['type' => 'noPay']), 'order')->result();
        
        //供应商搜索处理
        if (isset($result['list']) && is_array($result['list'])) {
            $sellercompanyids = array_unique(Arr::pluck($result['list'], 'sellercompanyid'));
            $sellercompanyids = implode(',',$sellercompanyids);
        }
        $companynames = $this->jrequest('api/company/'.$sellercompanyids, self::GET)->get();
        if (isset($companynames['result']) && is_array($companynames['result'])) {
            $companynames = array_combine(Arr::pluck($companynames['result'], 'companyid'), $companynames['result']);
        }
        if($result['list']){
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['companyname'] = isset($companynames[$v['sellercompanyid']]['companyname']) ? $companynames[$v['sellercompanyid']]['companyname'] : "--";
            }
        } 

        $this->search = $search;
        $this->page->total($result['total']);
        $this->view['list'] = $result['list'];
    }
    
    private function _searchMyLine() {
        $search = array(
            'kw' => Filter::str('kw'), // 搜索关键字
            'companykw' => Filter::str('companykw'), // 供应商
            'godate' => Filter::str('godate'), // 出团时间段
        );
        //出团时间段处理
        list($search['gobe'], $search['goed']) = explode('至', $search['godate']);
        // 供应商关键字搜索处理
        if (!empty($search['companykw'])) {
            $companyids = $this->jrequest("/api/company/query/" . urlencode($search['companykw']) . "/1", self::GET)->get();
            $companyids['result'] && $search['sellercompanyid'] = implode(',', array_unique(Arr::pluck($companyids['result'], 'id')));
        }
        return $search;
    }
    
    //保险判断游客是否符合
    public function action_ajaxCheckContract() {
        $search = [
            'orderId' => Filter::int('orderId'), // :性别
            'name' => Filter::str('name'), // :游客姓名
            'gender' => Filter::int('gender'), // :性别
            'category' => Filter::int('category'), // :证件类型
            'idCard' => Filter::str('idCard'), // :证件号
            'birthdayDate' => Filter::str('birthdayDate'), // :生日日期[格式按照这个格式yyyyMMdd]
            'mobile' => Filter::str('mobile'), // :手机
        ];
        $data = $this->jrequest('api/insurance/check/simple/guest', self::ADD,$search,'insurance')->get();
        $data['result'] && $res = $data['result']['returnCode'];
        echo json_encode(array("status"=>$res));die;
    }
    
    //添加鸿运发票地址
    public function action_ajaxyinvoaddr(){
        $params = array(
            'companyId' => $this->user['companyinfo']['id'],
//            'memberid' => $this->user['memberinfo']['id'],
            'name' => Filter::str('name'),
            'mobile' => Filter::str('mobile'),
            'address' => Filter::str('address'),
            'zipCode'=> Filter::str('num'),
        );
        $res = Controller_Admin_Orderservice::addinvoaddr($params,$this);
        echo json_encode(['status' => ($res ? 1: 0) , 'val' => ($res ? : '创建地址失败') ]);exit;    
    }
    
    //鸿运买家发票信息 
    public function action_ajaxinvoinfo(){
        $res = [];
        $status = 0;
        $params = array(
            'companyid' => $this->user['companyinfo']['id'],
        );
 
        $res = Controller_Admin_Orderservice::getbuyerinvoinfo($params,$this);

        $res['addr'] = Controller_Admin_Orderservice::getinvoaddr(array('companyid'=> $this->user['companyinfo']['id']),$this);
        $res['addrcout'] = count($res['addr']);
        
        if($res['enterpriseName'] && $res['registDetailAddress'] && $res['tel'] && $res['depositaryBank'] && $res['accountNo'] && $res['taxNo']){
            $status = 1;
        }
        echo json_encode(['status' =>  $status, 'val' => ($res ? : '公司信息不全') ]);exit; 
    }
    
    
    
    //鸿运开发提交请求
    public function action_ajaxpostinvo(){
        $msg = 0 ;
        $orderid = Filter::int('orderid');
        $aorder = $this->getOrder($orderid, 'order/index');
        if(empty($aorder)){ echo $msg;exit;}
        // 支付锁定
        $itime = time() - $aorder['paystarttime'];
        if($itime < 3600 || $aorder['ispay'] == 1) {
           echo $msg;exit;
        }
        
        $params = array(
            'orderId' =>  $orderid,//线路订单Id
            'expressAddressId' =>  Filter::int('hyexpress'),//发票地址Id
            'buyerCompanyId' =>  $this->user['orderinfo']['companyid'],//买家公司id
            'invoiceCategory' => Filter::int('addedtax') ==1 ? 0 : 1,
            'invoiceTitle' =>  $this->user['companyinfo']['companyname'],//发票抬头
            'invoiceType' => Filter::int('hyheader') ,//1 旅游费；2 团费
            'invoiceAmount' =>  $aorder['payamount']?:0,//开票金额
            'isPay' =>  $aorder['ispay'],//0 未支付；1 已支付
            'operationId' =>  $this->user['orderinfo']['id'],//操作人ID
            'operationName' =>  $this->user['orderinfo']['realname'],//操作人姓名
            
        );//print_r($params);exit;
        $msgs = Controller_Admin_Orderservice::addinvo($params,$this);
        $msgs && Controller_Admin_Orderservice::upinvostat(['id' => $orderid , 'status' =>3 ,'type' => 'editinvo'],$this);
        echo $msgs?1:0 ;exit;
    }
    
    
    //鸿运验证买家发票信息 
    public function action_ajaxhyinvoinfo(){
        $res = [];
        $status = 0;
        $result = Controller_Admin_Orderservice::checkcompanyinfo(array('buycompanyid'=> $this->user['companyinfo']['id']),$this);//专票和普票是否开通
        $res['addr'] = Controller_Admin_Orderservice::getinvoaddr(array('companyid'=> $this->user['companyinfo']['id']),$this); //地址
        $res['addrcout'] = count($res['addr']);
        //专票信息
        $res['zpinfo'] =  Controller_Admin_Orderservice::getzpapi(array('buycompanyid'=> $this->user['companyinfo']['id']), $this);
        echo json_encode(['status' =>  $result['chksealpath']?:($result['chkzp']?:0), 'val' => (array_merge($res,$result) ? : '公司信息不全') ]);exit; 
    }
    
    //ajax取消鸿运发票操作
    public function action_ajaxcancelinvo() {
        $result = array('res' => 0);
        $orderid = Filter::int('orderid');
        $invoid = Filter::int('invoid');
        //订单信息服务
        $order = $this->getOrder($orderid, 'order/index');
        // 支付锁定
        $itime = time() - $order['paystarttime'];
        if($itime < 3600 || $order['ispay'] == 1) {
           $result['msg'] = '订单正在支付中，不能取消发票'; 
           echo json_encode($result);exit;
        }
        
        $params = array(
                    'orderId'=> $orderid,
                    'operationId' =>  $this->user['orderinfo']['id'],//操作人ID
                    'operationName' =>  $this->user['orderinfo']['realname'],//操作人姓名
                );
        $res = Controller_Admin_Orderservice::cancelinvo($params,$this);
        $result['msg'] = $res? '取消发票成功！': '取消发票失败！';
        $result['res'] = $res?1:0;
        echo json_encode($result);exit;
    }
    
    
    //活动线路预订积分不足发短信
    public function action_ajaxbooksendsms(){
        $memberid = Filter::int('memberid');
        $companyid = Filter::int('companyid');
        $resinte = $this->request('integral/integrallog', self::GET, array('type'=> 'chargelog', 'companyid' => $companyid), 'integral')->result();
        $res = $this->jrequest('api/member/'.$memberid, self::GET,[],"member")->result();
        $redis = RedisDB::getRedis();
        if($redis){
            $createtime =  isset($resinte['detail']['createtime']) && $resinte['detail']['createtime'] ? $resinte['detail']['createtime'] : 1;
            $php_bookintersms = $redis->get('php_bookintersms'.$companyid);
            if(isset($res[0]['mobile']) && $res[0]['mobile'] && ( !$php_bookintersms || ($createtime > $php_bookintersms) )){
                $this->_sendSms($res[0]['mobile'],'您的活动线路因积分不足导致组团社无法预订，请尽快充值！');
                $redis->set('php_bookintersms'.$companyid,$createtime,60*60*24*60);//缓存60d
            }
        }
        exit;
        
    }
    
    //验证专票信息是否验证通过
    public  function action_ajaxcheckzpinvo(){
        $res = Controller_Admin_Orderservice::checkzpinvo(array('companyid'=> $this->user['companyinfo']['id']),$this);
        echo $res;exit;
    }
    
    //ajax取消鸿运发票地址操作
    public function action_ajaxdelinvoaddr() {
        $result = array('res' => 0);
        $id = Filter::int('addid');
        $params = array(
            'companyId' =>  $this->user['companyinfo']['id'],
            'tbInvoiceExpressAddressId' =>  $id,
        );
        $res = Controller_Admin_Orderservice::delinvoaddr($params,$this);
        $result['msg'] = $res? '删除成功！': '删除失败！';
        $result['res'] = $res?1:0;
        echo json_encode($result);exit;
    }
    
    public function action_fenqi(){
        $orderid = Filter::int('orderid');
        $totalprice = Filter::str('totalprice');
        $sucess = $this->jrequest('credtrip/merchantInfo/'.$this->user['companyinfo']['id'], self::GET,[],'pay')->get();
        if($sucess['code'] == '400'){
            $res = array('res'=>0);
        }elseif($sucess['result']['status'] == 'UNVERIFY' && $sucess['code'] == '200'){
            $res = array('res'=>5);
        }elseif($sucess['result']['status'] != 'ACTIVE' && $sucess['code'] == '200'){
            $res = array('res'=>3);
        }elseif (((int)$sucess['result']['balCredit']/100)< $totalprice){
                $res = array('res'=>4,'price'=>((int)$sucess['result']['balCredit']/100));
        }else{
            $re = $this->request('order/index',self::UPDATE,array('orderid'=>$orderid,'type'=>'fenqi','state'=>1),'order')->get();
                if($re['code'] == 200 && $re['result']){
                    $res = array('res'=>1);
                }else{
                    $res = array('res'=>2);
                }
            }
        echo json_encode($res);die;
    }

    public function action_orderrepay(){
        if (!($orderid = Filter::str('orderid', ''))) {
            $this->showMessage('获取订单信息失败', 'order.html');
        }
        $param = array(
            'orderid' => trim($orderid, ','),//批量支付时，多个id用，分隔
            'paystyle' => 4,//支付类型 1=积分订单
            'website' => $this->websitevalue[$this->platform],//平台1-cy 2-ht， 4-mc
            'memberid' => $this->user['memberinfo']['id'],//买家Id,
            'companyid' => $this->user['companyinfo']['id'],//公司id
            'platform' => $this->platform,//平台
        );
        $apiUrl = 'http://' . $this->sitedomain['pay'][$this->platform] . "/buyer?param=" . base64_encode(json_encode($param));
        header("Location: " . $apiUrl);
    }

    public function getCityId($params){
        $res = $this->jrequest('api/company/baseInfo/'.$params,self::GET)->get();
        return $res['code']==200?$res['result']['cityId']:null;
    }
}
