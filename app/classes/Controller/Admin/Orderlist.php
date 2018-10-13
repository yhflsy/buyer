<?php

class Controller_Admin_Orderlist extends Controller_Admin_Orderbase {

    protected $orderstatus; //订单类型

    public function before() {
        parent::before();
        $this->orderstatus = Kohana::$config->load('common.orderstatus');
//        $this->themes = 'web.tripb2b.v3.1';
    }
    
    public function action_index(){
        $this->_eventAnalysis('10015');
        $this->_check(11101);
        if( $this->user['companyinfo']['companytype'] == 1 &&  $this->user['companyinfo']['isseller']==0 ){
            Header("HTTP/1.1 303 See Other"); 
            $url = "/order.largetour.html";
            Header("Location: $url"); 
            exit; 
        }
        $mg = Filter::int('mg', -1); //消息
        $members = $this->jrequest('api/member/list', self::ADD, ['companyid' => $this->user['companyinfo']['id'], 'pagesize' => 100, 'curpageno' => 1])->get(); 
        if(!in_array(202, $this->order202)){
            foreach ((array)$members['result']['list'] as $k => $v) {
                if(!in_array((int)$this->user['memberinfo']['id'], $v)){
                    unset($members['result']['list'][$k]);
                }
            }
        }
         $this->view = [
            'members' => $members['result']['list'],
            'mg'=>$mg,
            'state'=>Filter::int('state', -1),
            'ispay' => Filter::int('ispay', -10), //支付状态
         ];
    }
    
    private  function _getParams(){
        $search =  [
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
            'companykw' => Filter::str('companykw'), // 供应商
            'operator' => 4, //用于计调搜索
            'cancel' =>  Filter::int('cancel',0),
            'fenqi' => Filter::int('fenqi'),
            'priceishidden' => Filter::int('hidden', 0),
        ];
        $this->_search($search);
        return  $search?:[];
    }

    public function action_ajaxIndex() {
        $search = $this->_getParams();
        $this->_searchMessage($search);//根据消息状态查询订单
        $params = array(
            'type' => 'list',
            'large' => 1,
            'companyid' => $this->user['companyinfo']['id'],
            'platforms' => 1,
            'search' => json_encode($search),
            'page' => Filter::int('p'),
            'pagesize' => Filter::int('ps', 10),
            'export' => Filter::int('export'), //导出列表标识
        );
        if (!in_array(11801, json_decode($this->roles['roleids']))) {   //判断用户是否拥有查看所有订单权限
            $params['memberid'] = $this->user['memberinfo']['id'];
        }
        $siteid = [];//站点id
        $data = $this->request('order/index', self::GET, $params, 'order')->result();
        $this->_matchLine($data,$params['export'],$siteid);
        //合同
        if($data['list']){
            $data['list'] = $this->_getContract($data['list']);
        }
        //信诚支付
        if($data['list']){
            $data['list'] = $this->_getXincheng($data['list']);
        }
        $siteinfo = $this->_getSiteName($siteid);  //站点
        $data['siteinfo'] = $siteinfo;
        if($data['list']){
            $data['status'] = '1';
        }
        $ispay = $this->jrequest("api/company/info/{$this->user['companyinfo']['id']}", self::GET)->result();
        $data['buyerispay']=  $ispay['ispay'];
        $data['cyindex'] = $this->sitedomain['index'];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        //导出
        $params['export'] &&  $this->_export($data);
        die;
    }
    
    // 导出
    public  function action_orderexport(){
        $search = $this->_getParams();
        $params = array(
            'type' => 'list',
            'large' => 1,
            'companyid' => $this->user['companyinfo']['id'],
            'platforms' => 1,
            'search' => json_encode($search),
            'page' => Filter::int('p'),
            'pagesize' => Filter::int('ps', 10),
            'export' => Filter::int('export'), //导出列表标识
        );
        $siteid = [];//站点id
        $ispay = ['未付','已付'];
        $data = $this->request('order/index', self::GET, $params, 'order')->result();
        $this->_matchLine($data,$params['export'],$siteid);
        if(!empty($data['list']) && is_array($data['list']) ){
            $list['title'] = array( '序号','出团日期','回团日期','订单号','线路名称', '地接社','订单人数', '订单金额','下单人','下单时间', '支付状态','支付时间','支付','立减','订单状态' );
            if($search['ispay'] == 2){
                foreach ($data['list'] as $k => $v) {
                    $list['data'][$k] = [$k+1,date("Y-m-d",$v['gotime']),date("Y-m-d",$v['backtime']),$v['orderid'],$v['linetitle'],$v['selleropeartorcompanyname'],($v['adult']+$v['child']),$v['totalprice'],$v['buyername'],date("Y-m-d",$v['createtime']),$ispay[$v['ispay']],'','','',$this->orderstatus[$v['state']]];
                }
            }else{
                foreach ($data['list'] as $k => $v) {
                    $paytime = $v['paytime'] ? date("Y-m-d",$v['paytime']) : '';
                    $list['data'][$k] = [$k+1,date("Y-m-d",$v['gotime']),date("Y-m-d",$v['backtime']),$v['orderid'],$v['linetitle'],$v['selleropeartorcompanyname'],($v['adult']+$v['child']),$v['totalprice'],$v['buyername'],date("Y-m-d",$v['createtime']),$ispay[$v['ispay']], $paytime,$v['payamount'],$v['totalsubtract'],$this->orderstatus[$v['state']]];
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
          $search['sellercompanyid'] = $search['sellercompanyid']?:1;
        }
        list($search['paybe'], $search['payed']) = explode('至', $search['paydate']);
    }
    
    // 列表处理
    private function _matchLine(&$aOrderList,$export,&$site_id) {
        $timestamp = $_SERVER['REQUEST_TIME'];
        $members = [];
        if ($aOrderList['list']) {
            $buyermemberids = [];
            $sellermemberids = [];
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
                //鸿运发票处理
                $hyinorder = Controller_Admin_Orderservice::checkorderinvo(array('orderIds' => $ids),$this);
            }
            $refundtype  = Kohana::$config->load('common.refundtype');
            $orderstatus = Kohana::$config->load('common.neworderstatus');
            foreach ($aOrderList['list'] as $k => $v) {
                // 线路处理
                $aOrderList['list'][$k]['brand'] = '';
                if(isset($aLineList['list'][$v['lineid']])){
                    $v['linediscount'] =  $aLineList['list'][$v['lineid']]['platformdiscount'];
                    $v['discountamount'] = $aLineList['list'][$v['lineid']]['discountamount'];
                    $aOrderList['list'][$k]['gocity'] =  $aLineList['list'][$v['lineid']]['gocity'];
                    $aOrderList['list'][$k]['brand'] = $aLineList['list'][$v['lineid']]['brandtitle'];
                    $aOrderList['list'][$k]['subtitle'] = $aLineList['list'][$v['lineid']]['subtitle'];
                    $destination = json_decode($aLineList['list'][$v['lineid']]['destination'], true);
                    $aOrderList['list'][$k]['destination'] = $destination['city']?$destination['city']:$destination[0]['city'];
                    $aOrderList['list'][$k]['linegotraffic'] = $aOrderList['list'][$k]['gotraffic'] ? $aOrderList['list'][$k]['gotraffic'] : $aLineList['list'][$v['lineid']]['gotraffic'];
                    $aOrderList['list'][$k]['linebacktraffic'] = $aOrderList['list'][$k]['backtraffic'] ? $aOrderList['list'][$k]['backtraffic'] : $aLineList['list'][$v['lineid']]['backtraffic'];
                }
                //保险处理
                $aOrderList['list'][$k]['insurance'] =  $insurance[$v['id']]?:[];
                if($v['ispay']==1){
                    if($v['refundtype']>0){
                        $aOrderList['list'][$k]['stateNmae']  = $refundtype[$v['refundtype']];
                    }elseif($v['stageday']>0 && $v['stageconfirm']==2 && $v['endpaytime'] <1 && $v['ispaylend']==0){
                        $aOrderList['list'][$k]['stateNmae'] = '待放款';
                    }elseif($v['stageday']>0 && $v['stageconfirm']==2 && $v['endpaytime'] <1 && $v['ispaylend']==1){
                        $aOrderList['list'][$k]['stateNmae'] = '待还款';
                    }elseif($v['stageday']>0 && $v['stageconfirm']==2 && $v['endpaytime'] > 1 ){
                        $aOrderList['list'][$k]['stateNmae'] = '已还款';
                    }else{
                        $aOrderList['list'][$k]['stateNmae']  = '已支付';
                    }
                }else{
                    $aOrderList['list'][$k]['stateNmae']  = $orderstatus[$v['state']];
                }
                if($v['state'] ==4 && $v['refundtype']<1){
                    $aOrderList['list'][$k]['stateNmae']= $v['ispay']?'已付(已出票)':'待付(已出票)';
                }
                // 计调处理
                $aOrderList['list'][$k]['time'] = $timestamp;
                $aOrderList['list'][$k]['gotimes'] = date("Y-m-d",$aOrderList['list'][$k]['gotime']);
                $aOrderList['list'][$k]['paytimes'] = date("Y-m-d h:i:s",$aOrderList['list'][$k]['paytime']);
                $aOrderList['list'][$k]['backtimes'] = date("Y-m-d",$aOrderList['list'][$k]['backtime']);
                $aOrderList['list'][$k]['createtimes'] = date("Y-m-d h:i:s",$aOrderList['list'][$k]['createtime']);
                $aOrderList['list'][$k]['selleropeartorname'] = isset($memberlist[$v['selleropeartorid']]['realname']) ? $memberlist[$v['selleropeartorid']]['realname'] : "--";
                $aOrderList['list'][$k]['selleropeartorcompanyname'] = isset($memberlist[$v['selleropeartorid']]['companyname']) ? $memberlist[$v['selleropeartorid']]['companyname'] : "--";
                $aOrderList['list'][$k]['selleropeartormobile'] = isset($memberlist[$v['selleropeartorid']]['mobile']) ? $memberlist[$v['selleropeartorid']]['mobile'] : "--";
                $aOrderList['list'][$k]['buyername'] = isset($memberlist[$v['buyerid']]['realname']) ? $memberlist[$v['buyerid']]['realname'] : "--";
                $aOrderList['list'][$k]['selleropeartorusernamee'] = isset($memberlist[$v['selleropeartorid']]['username']) ? $memberlist[$v['selleropeartorid']]['username'] : "--";
                $aOrderList['list'][$k]['sellerphotopath'] = isset($memberlist[$v['selleropeartorid']]['photopath']) ? $memberlist[$v['selleropeartorid']]['photopath'].'/'.$memberlist[$v['selleropeartorid']]['photopath'] : '';
                $aOrderList['list'][$k]['goTrafficName']  = common::dealSellerTract($aOrderList['list'][$k]['linegotraffic']);
                $aOrderList['list'][$k]['backTrafficName']  = common::dealSellerTract($aOrderList['list'][$k]['linebacktraffic']);
                $aOrderList['list'][$k]['appraise'] = $timestamp> ($aOrderList['list'][$k]['backtime'] + 86400 ) ? 1 : 0; // 评价
                $aOrderList['list'][$k]['refund'] = (date('Ymd',$timestamp) - date('Ymd',$aOrderList['list'][$k]['backtime'])) >0 ? 1 : 0;  // 退款
                $aOrderList['list'][$k]['refund15days'] = ($timestamp - $aOrderList['list'][$k]['backtime']) >3600*24*30 ? 1 : 0;;  // 回团15天后没有停止退款操作
                $aOrderList['list'][$k]['stageday']>0&& $aOrderList['list'][$k]['repaytime'] = date('Y-m-d',$v['paytime']+$v['stageday']*24*3600);
                // 财务连接
                $aOrderList['list'][$k]['financepay'] =0;
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
                    $aOrderList['list'][$k]['ret'] = $this->_payTime($v,$timestamp);
                }
                $aOrderList['list'][$k]['movetoplatform'] = $autoplatform[$aOrderList['list'][$k]['ordersite']];// 订单跳转平台
                //收集所有站点id
                $site_id[] = $v['siteid'];
                //鸿运发票，隐藏
                $aOrderList['list'][$k]['ishyorder'] = $hyinorder[$v['id']];                
            }
            $site_id = $site_id ? array_unique($site_id) : [];
        }
    }
    // 订单列表和财务互通
    private function  _financePay($finance,$orderid){
        if(is_array($finance) && isset($finance[$orderid])){
            return  $finance[$orderid]['received'] > 0 ? 1 : 0;
        }
        return 0;
    }
    // 线下是否已付清
    public function _ispayed($finance,$orderid){
        if(is_array($finance) && isset($finance[$orderid])){
            return  $finance[$orderid]['received'] == $finance[$orderid]['receivable'] ? 1 : 0;
        }
        return 0;
    }
    // 立减处理
    private function _payTime($order,$nowtime){
        $ret = ['countdown' => 0, 'tips' => '', 'discount' => 0,'tipico' =>'','peopleSum'=>0];
        if (!in_array($order['state'], [1, 4]) || $order['ispay'] || $order['category']==1) {
            return $ret;
        }
        //立减和立减倒计时
        $subtract= 0;
        $peopleSum = 0;
        $lijiantime = [];
        $lijian = $this->request('order/pay', self::GET, ['webSite' => $order['ordersite'], 'siteId' => $order['siteid'], 'sellerId' => $order['sellercompanyid'], 'buyerId' => $order['buyercompanyid'], 'type' => "getDiscount"], 'order')->result();
        $sellerHour = ($order['confirmtime'] +$lijian['sellerHour']*60*60)-$nowtime;
        $platformHour = ($order['confirmtime'] +$lijian['platformHour']*60*60) -$nowtime;
        $lineHour = $order['confirmtime'] + 86400 -$nowtime ;
        if($sellerHour>0){
            $subtract +=  $lijian['sellerPercent']+$lijian['platformAdditionalPercent'];
            $lijiantime[] = $sellerHour;
        }
        if($platformHour>0){
            $subtract +=  $lijian['platformPercent'];
            $lijiantime[] = $platformHour;
        }
        if($lineHour>0 && $order['linediscount']>0){
            $subtract += $order['linediscount'];
            $lijiantime[] = $lineHour;
        }
        if($lineHour>0 && $order['discountamount']>0){
            $lijiantime[] = $lineHour;
            $peopleSum = ($order['adult']+$order['child']+$order['baby'])*$order['discountamount'];
        }
        $lijiantime && asort($lijiantime);
        if(($subtract>0|| $peopleSum>0) && $lijiantime){
            $lijiantime = current($lijiantime);
            $ret = [];
            if(($order['gotime']+86399)>$nowtime && $lijiantime>0 && $order['confirmtime']<$order['gotime']){
                $ret['discount'] = $subtract;
                $ret['countdown'] = $lijiantime;
                $ret['tips'] = "之前支付 立减{$subtract}%(指定期限之前支付有效）";
                $ret['peopleSum'] = $peopleSum;
            }else{
                $ret['tips'] = "出团当天下单/支付或订单确认后超过24小时未支付，不享受立减！";
            }
        }
        return $ret;
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
        echo json_encode($result);exit;
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
    
    // 订单支付(旅付宝)
    public function action_orderPay () {
        $this->_check(11107);
        $paytype = Filter::int('paytype');
        if ($this->request->method() == Request::POST || Filter::str("isnew")){
            $this->params = array(
                'orderid' => Filter::str("orderid"), //订单id
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
    
    //付费保险
    public function action_insurancePay(){
        $this->_eventAnalysis('10033');
        $lineOrderId = Filter::str('orderid');
        $detail = $this->jrequest('api/insurance/order/quick/nopay', self::ADD, ['lineOrderId' => $lineOrderId ],"insurance")->result();
        echo json_encode($detail[0]);
        exit;
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
    
    // ajax线路评价
    public function action_ajaxAppraise() {
        $this->_check(11104);
        if ($this->request->method() != Request::POST) {
            return;
        } 

        $params = array(
            'orderid' => Filter::str('orderid'),
            'buyercompanyid' =>  Filter::str('buyercompanyid'),
            'sellercompanyid' =>Filter::str('sellercompanyid'),
            'memberid' => Filter::str('memberid'),
            'detail' => Filter::str('detail'),
            'companyappraise' => Filter::str('scoreShoper'),
            'lineappraise' => Filter::str('lineappraise'),
            'appraise' => Filter::str('scoreXingcheng').','.Filter::str('scoreTravel').','.Filter::str('scoreZhusu').','.Filter::str('scoreCanyin').','.Filter::str('scoreLeader'),
            'createtime' => time(),
            'lineid'=>Filter::str('lineid'),
        );
        $line = $this->request('line/date', self::GET, ['appraise' =>1,'dateid'=>$params['lineid']], 'line')->get();
        $this->_changer($line);
        $params['lineid'] =$line['detail']['lineid'];
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
    
    

    

}
