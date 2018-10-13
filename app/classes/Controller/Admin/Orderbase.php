<?php

/**
 * 订单基本服务
 */
class Controller_Admin_Orderbase extends Controller_Base {

    private $surpassLoginCheck = false;
    public $order202 = [];
    
    public function before() {
        parent::before();
        $this->_ignoreChk(); //过滤是否登录
        $this->_checkCsrf();
        if ($this->user){
             $this->user['orderinfo'] = array(
                'id' => $this->user['memberinfo']['id'],
                'companyid' => $this->user['companyinfo']['id'],
                'realname' => $this->user['memberinfo']['realname'],
                'mobile' => $this->user['memberinfo']['mobile'],
                'tel' => $this->user['memberinfo']['tel'],
                'fax' => $this->user['memberinfo']['fax'],
            ); 
             
           if(!empty($this->user['roles'])){
               $this->order202 = Arr::pluck($this->user['roles'], 'roleid');
           }
        }
    }
     
     private function _checkCsrf(){
        if($this->request->method() == Request::POST){
            if($csrfcode = Filter::str('CSRFCODE')){
                if(isset($_SESSION['CSRFCODE'.$this->user['memberinfo']['id']]) && $_SESSION['CSRFCODE'.$this->user['memberinfo']['id']] == $csrfcode){
                    $this->showMessage('订单已提交,请勿重复刷新提交', '/order.html');
                }else{
                    $_SESSION['CSRFCODE'.$this->user['memberinfo']['id']] = $csrfcode;
                }
                
            }
        }
    }
    
    
     /**
     * 忽视权限验证
     */
    private function _ignoreChk() {
        if ($this->surpassLoginCheck !== TRUE) {
            if ((in_array($this->directory, ['uc']) && !$this->user['memberinfo']['id'])) {
                $this->contron_arr = ['print'];
                if (!in_array($this->controller, $this->contron_arr)) {
                    $this->showMessage('请先登录', 'http://'.$this->sitedomain['index'][($this->platform ? $this->platform : 'tripb2b')]);
                }
            }
        }
    }
    


    public function after() {
        parent::after();
    }

    //单条订单信息
    protected function getOrder($id, $url) {
        //大社查看订单
        $aorderp = array(
            'type' => 'detail',
            'companyid' => $this->user['orderinfo']['companyid'],
            'id' => $id,
        );
        if($this->user['companyinfo']['companytype'] ==1){
            $aorderp['large'] = 1;
        }
        $order = $this->request($url, self::GET, $aorderp, 'order')->result();
        $aorder = (array) $order['detail'];
        return $aorder;
    }

    //订单修改服务
    protected function editOrder($url, $orderid, $line, $otherinfo, $guestinfo, $delinfo, $invoiceinfo = []) {
        $params = array(
            'type' => 'edit',
            'id' => $orderid,
            'companyid' => $this->user['orderinfo']['companyid'],
            'platforms' => 1,
            'lineinfo' => json_encode($line),
            'otherinfo' => json_encode($otherinfo),
            'guestinfo' => json_encode($guestinfo),
            'delinfo' => json_encode($delinfo),
            'invoiceinfo' => json_encode($invoiceinfo),
        );
        $code = $this->request($url, self::UPDATE, $params, 'order')->get();
        return $code;
    }
    
    protected function addOrderConfirmFinanceLog($aOrder){
        $params = array(
            'sellerid' => $aOrder['sellercompanyid'],
            'buyerid' => $aOrder['buyercompanyid'],
            'financenumber' => 'Or' . date('YmdHis') . rand(100, 999),
            'receivable' => $aOrder['totalprice'],
            'received' => 0,
            'updatetime' => time(),
            'category' => 0,
            'orderid' => $aOrder['id'],
            'memberid' => $aOrder['buyerid'],
            'createtime' => time(),
            'website' => $aOrder['website']
        );
        $this->request('finance/index', Request::POST, array_merge(array('type' => 'finance'), $params), 'finance')->get();
    }

    //线路基本信息
    protected function getLine($id) {
        $line = $this->request('line/index', self::GET, array('type' => 'orderLineDetail', 'dateid' => $id), 'line')->result(); //'type' => 'simpledetail'
        $aline = (array) $line['detail'];
        $aline['active'] = [];
//        $aFeeOther = $this->request('line/other', self::GET, array('type' => 'fee', 'lineid' => $aline['lineid'], 'siteid' => $aline['siteid'], 'departureid' => $aline['departureid']))->result();
//        if ($aFeeOther['list']) {
//            foreach ($aFeeOther['list'] as $v) {
//                if ($v['category'] == 0) {
//                    $aline['active'][] = $v;
//                }
//            }
//        }
        return $aline;
    }

    //获得接送列表
    protected function getTake($params,$istianchi=0,$tcLineId=0) {
        if($istianchi){
            $takeinfos = $this->request('api/take/tianchi/getTakeByLineId', self::GET, array('lineId' => $tcLineId), 'fleet')->result();
            foreach((array)$takeinfos  as $key => $value){
                $takeinfos[$key]['tag'] = $value['category']==1?2:($value['category']==2?0:1);
                $multiple = $value['category']==2 ?2:1;
                if($value['percentage']){
                    $amt= [
                        intval($value['product']['subPrice1'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice2'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice3'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice4'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice5'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice6'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice7'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice8'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice9'] *$multiple* $value['percentage']),
                        intval($value['product']['subPrice10'] *$multiple* $value['percentage']),
                    ];
                }elseif($value['fixed']){
                    $amt= [
                        $value['product']['subPrice1']+$value['fixed']>0?intval($value['product']['subPrice1']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice2']+$value['fixed']>0?intval($value['product']['subPrice2']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice3']+$value['fixed']>0?intval($value['product']['subPrice3']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice4']+$value['fixed']>0?intval($value['product']['subPrice4']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice5']+$value['fixed']>0?intval($value['product']['subPrice5']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice6']+$value['fixed']>0?intval($value['product']['subPrice6']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice7']+$value['fixed']>0?intval($value['product']['subPrice7']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice8']+$value['fixed']>0?intval($value['product']['subPrice8']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice9']+$value['fixed']>0?intval($value['product']['subPrice9']*$multiple + $value['fixed']):0,
                        $value['product']['subPrice10']+$value['fixed']>0?intval($value['product']['subPrice10']*$multiple + $value['fixed']):0,
                    ]; 
                }else{
                    $amt= [
                        $value['product']['subPrice1']*$multiple,
                        $value['product']['subPrice2']*$multiple,
                        $value['product']['subPrice3']*$multiple,
                        $value['product']['subPrice4']*$multiple,
                        $value['product']['subPrice5']*$multiple,
                        $value['product']['subPrice6']*$multiple,
                        $value['product']['subPrice7']*$multiple,
                        $value['product']['subPrice8']*$multiple,
                        $value['product']['subPrice9']*$multiple,
                        $value['product']['subPrice10']*$multiple,
                    ]; 
                }
                $takeinfos[$key]['amtArr'] = $amt;
                $takeinfos[$key]['amt']  = implode(',', $amt);
                $takeinfos[$key]['tianchi'] = 1; 
            } 
            $takeinfo['list'] = $takeinfos;
        }else{
            $takeinfo = $this->request('line/buyerfee', self::GET, array('type' => 'date', 'dateid' => $params['lineid'], 'tag' => $params['taketype']), 'line')->result();
            if($takeinfo['list']){
               foreach($takeinfo['list'] as $key => $value){
                   $takeinfo['list'][$key]['setoff'] = explode('-', $value['title']);
                   $takeinfo['list'][$key]['dest'] = explode('-', $value['detail']);
                   $takeinfo['list'][$key]['tianchi']  =0;
               }
            }
        }
        return $takeinfo;
          
    }

    //tianchi最新价格
    protected  function newTianChiAmt($takeid,$lineid=0){
        $takeinfos = $this->request('api/take/tianchi/getTakeByTianchiId/', self::GET, array('tianchiId' => $takeid,'lineId'=>$lineid), 'fleet')->result();
        $data = json_decode($takeinfos['data'],true);
            $amt= [
                $data['SubPrice1'],
                $data['SubPrice2'],
                $data['SubPrice3'],
                $data['SubPrice4'],
                $data['SubPrice5'],
                $data['SubPrice6'],
                $data['SubPrice7'],
                $data['SubPrice8'],
                $data['SubPrice9'],
                $data['SubPrice10'],
            ]; 
        $peopleNum = Filter::int('adult',0)+Filter::int('child',0); //成人
        $peopleNum = $peopleNum>=1?$peopleNum-1:$peopleNum;
        $peopleNum = $peopleNum>=9?9:$peopleNum;
        $multiple = $takeinfos['category']==2 ? 2 :1;
        if($takeinfos['percentage']){
            $takeAmt = intval($amt[$peopleNum]*$multiple*$takeinfos['percentage']);
        }elseif($takeinfos['fixed']){
            $takeAmt = intval($amt[$peopleNum]*$multiple+$takeinfos['fixed']);
        }else{
            $takeAmt = $amt[$peopleNum]*$multiple;
        }
        return $takeAmt>0?$takeAmt:0;
    }

    //获得单条接送
    protected function getTakeOne($takeid,$istianchi=0,$price=0,$lineid=0,$number=0){
        if($istianchi){
            $takeinfos = $this->request('api/take/tianchi/getTakeByTianchiId/', self::GET, array('tianchiId' => $takeid,'lineId'=>$lineid), 'fleet')->result();
            $data = json_decode($takeinfos['data'],true);
            $multiple = $takeinfos['category']==2 ? 2 :1;
            if($takeinfos['percentage']){
                $amt= [
                    intval($data['SubPrice1']*$multiple * $takeinfos['percentage']),
                    intval($data['SubPrice2'] *$multiple* $takeinfos['percentage']),
                    intval($data['SubPrice3'] *$multiple* $takeinfos['percentage']),
                    intval($data['SubPrice4'] *$multiple* $takeinfos['percentage']),
                    intval($data['SubPrice5']*$multiple * $takeinfos['percentage']),
                    intval($data['SubPrice6']*$multiple * $takeinfos['percentage']),
                    intval($data['SubPrice7'] *$multiple* $takeinfos['percentage']),
                    intval($data['SubPrice8'] *$multiple* $takeinfos['percentage']),
                    intval($data['SubPrice9'] *$multiple* $takeinfos['percentage']),
                    intval($data['SubPrice10'] *$multiple* $takeinfos['percentage']),
                ];
            }elseif($takeinfos['fixed']){
                $amt= [
                    $data['SubPrice1']+$takeinfos['fixed']>0?intval($data['SubPrice1']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice2']+$takeinfos['fixed']>0?intval($data['SubPrice2']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice3']+$takeinfos['fixed']>0?intval($data['SubPrice3']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice4']+$takeinfos['fixed']>0?intval($data['SubPrice4']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice5']+$takeinfos['fixed']>0?intval($data['SubPrice5']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice6']+$takeinfos['fixed']>0?intval($data['SubPrice6']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice7']+$takeinfos['fixed']>0?intval($data['SubPrice7']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice8']+$takeinfos['fixed']>0?intval($data['SubPrice8']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice9']+$takeinfos['fixed']>0?intval($data['SubPrice9']*$multiple + $takeinfos['fixed']):0,
                    $data['SubPrice10']+$takeinfos['fixed']>0?intval($data['SubPrice10']*$multiple + $takeinfos['fixed']):0,
                ];
            }else{
                $amt= [
                    $data['SubPrice1']*$multiple,
                    $data['SubPrice2']*$multiple,
                    $data['SubPrice3']*$multiple,
                    $data['SubPrice4']*$multiple,
                    $data['SubPrice5']*$multiple,
                    $data['SubPrice6']*$multiple,
                    $data['SubPrice7']*$multiple,
                    $data['SubPrice8']*$multiple,
                    $data['SubPrice9']*$multiple,
                    $data['SubPrice10']*$multiple,
                ];
            }
            $take['amt'] = implode(',', $amt);
            $take['tianchi'] = 1;
            $take['tag'] = $takeinfos['category']==1?2:($takeinfos['category']==2?0:1);
            $take['setoff'][0] = $takeinfos['townName'];
            $take['setoff'][2] = $data['LocationAddress'];
            $take['dest'][0] = $takeinfos['stationName'];
            $take['id'] = $data['ID'];
            $take['price'] = $price?:$amt[0];
            $take['tianchiprice'] = $amt[$number];
            $takeinfo['detail'] = $take;
        }else{
            $takeinfo = $this->request('line/other', self::GET, array('type' => 'feeother', 'id' => $takeid), 'line')->result();
            if($takeinfo['detail']){
                $takeinfo['detail']['setoff'] = explode('-',  $takeinfo['detail']['title']);
                $takeinfo['detail']['dest'] = explode('-', $takeinfo['detail']['detail']);
                $takeinfo['detail']['tianchi']=0;
            }
        }
        return $takeinfo;
         
    }
    

    //供应商标题与联系方式
    protected function getSellerinfo($companyid) {
        $company = [];
        $result = $this->jrequest('api/company/info/' . $companyid, self::GET)->get();
        if ($result['code'] == 200) {
            $company = $result['result'];
        }
        return $company;
    }
    
    //获取线路目的地下的客服
    protected function getSellerDesCell($params){
        $result = $this->request('system/callcenter', self::GET ,$params, 'base')->result();
        return (array) $result;
    }

    //获取出团日期
    protected function getGoTime($dateid){
        $dateList = $this->request('lines/details', self::GET, ['dateid' => $dateid, 'type' => 'dates'], 'line')->result();
        if ($dateList) {
            $weekday = Kohana::$config->load('common.weekday');
            foreach ((array)$dateList as $key => $value) {
                $dateList[$key]['goTime'] = date('Y-m-d', $value['gotime']);
                $dateList[$key]['weekday'] = $weekday[date('w', $value['gotime'])];
            }
        }
        return (array) $dateList;
    }

    //游客信息
    protected function getGuest($id) {
        $guests = $this->request('order/guest', self::GET, array('orderid' => $id), 'order')->result();
        return (array) $guests;
    }

    //订单日志
    protected function getLog($id) {
        $log = $this->request('order/log', self::GET, array('orderid' => $id), 'order')->result();
        if($log){
            $memberids = array_unique(Arr::pluck($log, 'memberid'));
            $readmemberids = array_unique(Arr::pluck($log, 'readmemberid'));
            $amember = $this->jrequest('api/member/' . implode(',', $memberids), self::GET)->result();

            $areadmember = $this->jrequest('api/member/' . implode(',', $readmemberids), self::GET)->result();
            $amember && $amember = array_combine(Arr::pluck($amember, 'id'), $amember);
            $areadmember && $areadmember = array_combine(Arr::pluck($areadmember, 'id'), $areadmember);
            foreach ($log as $k => $v) {
                $log[$k]['edituser'] = isset($amember[$v['memberid']]) ? $amember[$v['memberid']]['realname'] : '--';
                $log[$k]['readuser'] = isset($areadmember[$v['readmemberid']]) ? $areadmember[$v['readmemberid']]['realname'] : '--';
                $log[$k]['isSeller'] = isset($amember[$v['memberid']]) ? $amember[$v['memberid']]['isSeller'] :0;
            }
        }
        return (array) $log;
    }

    //订单价格明细
    protected function getPriceList($id) {
        $pricelist = $this->request('order/pricelist', self::GET, array('id' => $id, 'type' => 'list'), 'order')->result();
        return (array) $pricelist;
    }

    //删除订单消息
    protected function delOrderMsg($params) {
        $this->jrequest('del', self::DELETE, $params, 'message')->result();
    }

    //添加订单消息
    protected function addOrderMsg($params) {
        $this->jrequest('add', self::ADD, $params, 'message')->get();
    }
    
    //app推送消息
    protected function addAppMsg($params){
        $this->jrequest('pushapp', self::ADD, $params, 'push')->get();
    }
    
    //pc推送消息
     protected function addPcMsg($params){
        $this->jrequest('pushpc', self::ADD, $params, 'push')->get();
    }

    // 订单预定成功写日志
    protected function _addOrderLog($params) {
        $params['ip'] = Ip::GeIpAddress();
        $this->request('order/log', self::ADD, array('type' => 1, 'params' => $params), 'order')->result();
    }

    //修改订单日志
    protected function _editOrderLog($aNewOrderData, $aOldOrderData,$sellerid) {
        $params = array(
            'type' => 2,
            'memberid' => $this->user['orderinfo']['id'],
            'new' => $aNewOrderData,
            'old' => $aOldOrderData,
            'readmemberid' => $sellerid,
        );
        $this->request('order/log', self::ADD, $params, 'order')->result();
    }

    // 预定处理积分日志
    protected function _handleIntegralLog($code, $oLine, $cachename = 'book') {
        $dates = date('Y-m-d H:i:s');
        $params = array(
            'aIntegral' => array(
                ':companyid' => $oLine['companyid'],
                ':memberid' => $this->user['orderinfo']['id'],
                ':action' => 12,
                ':integral' => $code['result']['orderinfo']['totalintegral'],
                ':detail' => "组团社在{$dates}时下单冻结积分{$code['result']['orderinfo']['totalintegral']} 订单编号: {$code['result']['orderinfo']['orderid']}",
                ':lineid' => $oLine['dateid'],
                ':orderid' => $code['result']['id'],
                ':category' => 0
            )
        );
        
        if($code['result']['orderinfo']['totalintegral']){
           $codes = md5(json_encode($params). date("YmdHis") . rand(100, 999));
           $cparam = array(
               ':code' => $codes,
               ':params' => $params,
           );
           $this->request('integral/integrallog', self::ADD, array('type'=> 'create', 'params' => $cparam), 'integral')->result();     
           $params['aIntegral'][':code'] = $codes; 
        }
        $code['result']['orderinfo']['totalintegral'] ? $this->request('integral/sellintegral', self::ADD, $params, 'integral')->code() : 200;
    }

    //订单修改处理积分
    protected function _editIntegralLog($orderid, $aordernew, $order) {
        $dates = date('Y-m-d H:i:s');
        $ointer = $aordernew['totalintegral'] - $order['totalintegral'];
        $action = $ointer > 0 ? 12 : 3;
        $details = $ointer > 0 ? '冻结' : '解冻';
        $ointer = abs($ointer);
        $params = array(
            'aIntegral' => array(
                ':companyid' => $aordernew['sellercompanyid'],
                ':memberid' => $this->user['orderinfo']['id'],
                ':action' => $action,
                ':integral' => ($action == 12) ? $ointer : -1 * $ointer,
                ':detail' => "组团社在{$dates}时修改订单{$order['orderid']}{$details}积分{$ointer}",
                ':lineid' => $aordernew['lineid'],
                ':orderid' => $orderid,
                ':category' => 0
            )
        );
        
        if($ointer){
           $codes = md5(json_encode($params). date("YmdHis") . rand(100, 999));
           $cparam = array(
               ':code' => $codes,
               ':params' => $params,
           );
           $this->request('integral/integrallog', self::ADD, array('type'=> 'create', 'params' => $cparam), 'integral')->result();     
           $params['aIntegral'][':code'] = $codes; 
        }
        
        $ointer ? $this->request('integral/sellintegral', self::ADD, $params, 'integral')->code() : '';
    }

    //取消处理积分
    protected function _cancelIntegLog($aorder) {
        $dates = date('Y-m-d H:i:s');
        $params = array(
            'aIntegral' => array(
                ':companyid' => $aorder['sellercompanyid'],
                ':memberid' => $this->user['orderinfo']['id'],
                ':action' => 3,
                ':integral' => -1 * $aorder['totalintegral'],
                ':detail' => "组团社在{$dates}时取消订单{$aorder['orderid']}解冻积分{$aorder['totalintegral']}",
                ':lineid' => $aorder['lineid'],
                ':orderid' => $aorder['id'],
                ':category' => 0
            )
        );
        if($aorder['totalintegral']){
           $codes = md5(json_encode($params). date("YmdHis") . rand(100, 999));
           $cparam = array(
               ':code' => $codes,
               ':params' => $params,
           );
           $this->request('integral/integrallog', self::ADD, array('type'=> 'create', 'params' => $cparam), 'integral')->result();     
           $params['aIntegral'][':code'] = $codes; 
        }              
        $aorder['totalintegral'] ? $this->request('integral/sellintegral', self::ADD, $params, 'integral')->code() : '';
    }

    //订单恢复处理积分
    protected function _recoverIntegLog($aorder) {
        $dates = date('Y-m-d H:i:s');
        $params = array(
            'aIntegral' => array(
                ':companyid' => $aorder['sellercompanyid'],
                ':memberid' => $this->user['orderinfo']['id'],
                ':action' => 12,
                ':integral' => $aorder['totalintegral'],
                ':detail' => "组团社在{$dates}时恢复订单{$aorder['orderid']}冻结积分{$aorder['totalintegral']}",
                ':lineid' => $aorder['lineid'],
                ':orderid' => $aorder['id'],
                ':category' => 0
            )
        );
        if($aorder['totalintegral']){
           $codes = md5(json_encode($params). date("YmdHis") . rand(100, 999));
           $cparam = array(
               ':code' => $codes,
               ':params' => $params,
           );
           $this->request('integral/integrallog', self::ADD, array('type'=> 'create', 'params' => $cparam), 'integral')->result();     
           $params['aIntegral'][':code'] = $codes; 
        }                 
        $aorder['totalintegral'] ? $this->request('integral/sellintegral', self::ADD, $params, 'integral')->code() : '';
    }

    //订单修改删除人数处理
    protected function _dealGuest($guests, $guestinfo) {
        $delids = array_diff(Arr::pluck($guests, 'id'), $guestinfo['id']); //删除信息
        $category = array_intersect_key(Arr::pluck($guests, 'category'), $delids);
        return $delinfo = array_combine($delids, $category);
    }
    
    
    //订单修改订单数据修改前与修改后的数据
    protected function _dealOrderData($order, $aordernew, $guests, $guestsnew){
        $aOldOrderData = array(//老的订单数据
            'orderid' => $order['id'],
            'price' => $order['totalprice'],
            'contactname' => $order['contactname'],
            'tel' => $order['tel'],
            'mobile' => $order['mobile'],
            'fax' => $order['fax'],
            'detail' => $order['detail'],
            'selleropeartorrealname' => $order['selleropeartorrealname'],   //供应商计调名字
            'guests' => $guests
        );
        $aNewOrderData = array(//新的订单数据
            'orderid' => $order['id'],
            'price' => $aordernew['totalprice'],
            'contactname' => $aordernew['contactname'],
            'tel' => $aordernew['tel'],
            'mobile' => $aordernew['mobile'],
            'fax' => $aordernew['fax'],
            'detail' => $aordernew['detail'],
            'selleropeartorrealname' => $aordernew['selleropeartorrealname'],   //供应商计调名字
            'guests' => $guestsnew
        );
        return [$aOldOrderData,$aNewOrderData];
    }
    
    
    //获得供应商下面的所有计调
    protected function sellCompanyOpt($params){
        $data = $this->jrequest('api/member/list/', self::ADD, ['companyid' => $params['companyid'], 'pagesize' => 1000, 'curpageno' => 1])->result();
        return array('sellerlist' => array_filter(array_map(function ($v){if($v['isusable']){ return $v;}}, (array)$data['list'])));
    }
    
    //验证订单修改页面是否修改过（只验证 人数 预订单状态）
    protected function checkOrderMofiy($order, &$editinfo){
        $result = true;
        $oadult = Filter::int('oadult');
        $ochild = Filter::int('ochild');
        $obaby = Filter::int('obaby');
        $ostate = Filter::int('ostate');
        if(($oadult == $order['adult']) && ($ochild == $order['child']) && ($obaby == $order['baby']) && ($ostate == $order['state'])){
            $result = false;
        }
        $editinfo['oadult'] = $oadult;
        $editinfo['ochild'] = $ochild;
        $editinfo['obaby'] = $obaby;
        return $result;
    }
        
    
    //详情处理积分同步失败的缓存(暂时没有使用)
    /**
     * 
     * @param type $orderid 订单ID
     * @param type $action  操作的类型  order 正常 addseat 加位 reserve 预留
     */
    protected function _dealOrderCache($orderid = 0, $action = 'order'){
        if($action == 'order'){
            $bookcache = $this->cache->get('book'.$orderid);
            $bookcache ? ($this->_integralServerUrl(json_decode($bookcache,ture)) ==200 ? $this->cache->delete('book'.$orderid) : '' ): '';
        }elseif($action == 'reserve'){
            $bookcache = $this->cache->get('reservebook'.$orderid);
            $bookcache ? ($this->_integralServerUrl(json_decode($bookcache,ture)) ==200 ? $this->cache->delete('reservebook'.$orderid) : '' ): '';
        }elseif($action == 'addseat'){
            $bookcache = $this->cache->get('addseatbook'.$orderid);
            $bookcache ? ($this->_integralServerUrl(json_decode($bookcache,ture)) ==200 ? $this->cache->delete('addseatbook'.$orderid) : '' ): '';
        }
    }
   //操作积分服务的url
    protected function _integralServerUrl($params){
         return $this->request('integral/sellintegral', self::ADD, $params, 'integral')->code();
    }
    
        // 服务请求，数据由get转换成result
    protected function _changer(&$data,$url=NUll){
        
        if(intval($data['code']) != 200){
            if($url){
               $this->showErrMessage($data['code'], $data['message'], $url);  
            }else{
               $this->setErrToHeader($data); 
            }
        }
        $data = $data['result'];
    }
    
    
    //check发票活动的服务
    protected function _checkInvoiceDate($params){
        $res = false;
	return $res;
        $result = $this->jrequest('api/invoice/checkDate', self::ADD, $params, 'invoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
           $res =  $result['result'];
        }
       return $res;
    }
    
    //获得发票收获地址列表
    protected  function  getInvoiceAddr($params){//.$params['companyid']
        $res = [];
        $result = $this->jrequest('api/addr/buyerAddrs/'.$params['companyid'], self::GET, [], 'invoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //添加发票地址
    protected function addInvoiceAddr($params){
        $res = 0;
        $result = $this->jrequest('/api/addr/add', self::ADD, $params, 'invoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    
    //获取公司是否上传公章
    protected function getCompanySealPath(){
        $res = [];
        $result = $this->jrequest("api/company/info/{$this->user['companyinfo']['id']}", self::GET, [], 'member')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //订单编辑选中
    protected function checkOrderInvoice($params){
         $res = [];
        $result = $this->jrequest('api/invoice/loadInvoice/'.$params['orderid'], self::GET, [], 'invoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    
    //判断是否有公司平台接送
    protected function checkLineCompTake($params){
        $res = [];
        $result = $this->jrequest('api/take/line/'.$params['lineid'], self::GET, [], 'fleet')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //判断线路是否有免费保险
    protected function checkLineFreeInsurance($params){
        $res = 0;
        $result = $this->jrequest('api/insurance/freeinsurance/showrule', self::ADD, $params, 'insurance')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //线路中的免费保险
    protected function lineFreeInsurance($params){
        $res = [];
        $result = $this->jrequest('api/insurance/freeinsurance/rulelist', self::ADD, $params, 'insurance')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            foreach((array)$result['result'] as $k=>$v){
                if($params['days']>=$v['minDay'] && $params['days']<=$v['maxDay']){
                    $res[] = $v;
                }
            }

        }
        return $res;
    }
    
    //线路预订选择保险创建保险订单
    protected function createFreeInsuranOrder($orderinfo,$lineinfo,$productid,$freeStatus){
        $params = array(
            'lineOrderId' => $orderinfo['id'], //线路订单Id
            'productId' => $productid, //保险产品Id
            'rateId' => 0, //费率ID(签证险的情况，才传值）
            'orderCode' => $orderinfo['orderinfo']['orderid']?:$orderinfo['orderid'], //线路订单CODE
            'companyId' => $this->user['orderinfo']['companyid'], //买家公司id
            'memberId' => $this->user['orderinfo']['id'], //买家id
            'website' => $this->websitevalue[$this->platform], //订单来源（1：馨·驰誉 2：馨·欢途 4：美程）
            'destination' => json_decode($lineinfo['destination'], true)['city']?:(json_decode($lineinfo['destination'], true)['province']?:json_decode($lineinfo['destination'], true)[0]['city']), //目的地
            'days' => $lineinfo['days'], //保险天数
            'effectiveTime' => date('Y-m-d',$orderinfo['orderinfo']['gotime']?:$orderinfo['gotime']), //生效时间 格式:yyyy-MM-dd
            'deadTime' => date('Y-m-d',$orderinfo['orderinfo']['backtime']?:$orderinfo['backtime']), //失效时间 格式:yyyy-MM-dd
            'email' => '', //电子邮件 
            'siteId'  => $lineinfo['siteid'],
            'freeStatus' => $freeStatus, //购买方式[1:赠送,2:绑定销售]
        );
        $this->jrequest('api/insurance/order/free/create', self::ADD, $params, 'insurance')->code();
    }
    
    //西安站线路绑定保险
    protected function lineSaleInsurance($params){
        $res = [];
        $result = $this->jrequest('api/insurance/bundle/sales', self::ADD, $params, 'insurance')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;  
    }
    
    //易道用车卷
    public function _takeInfor($ordersite,$siteid){
        $takeinfo = [];
        
        $result = $this->request("api/take/shuttle/rule?webSite={$ordersite}&siteId={$siteid}", self::GET, [], "fleet")->get();
        if(isset($result['code']) && $result['code'] == 200){
            $takeinfo = $result['result'] ? $result['result'] : [];
        }
        if($takeinfo['hasRule'] && $takeinfo['percent']){
            return $takeinfo['percent'];
        }
        return -1;
    }

    // 合同
    public function action_ajaxContract() {
        $linecategory = Filter::int('linecategory');
        $result = $this->jrequest("api/company/info/{$this->user['companyinfo']['id']}", self::GET)->result();
        $params = [
            'parentid'=>$result['parentid'] ?:0,
            'companytype'=>$this->user['companyinfo']['companytype'],
            'companyid'=>$this->user['companyinfo']['id'],
        ];
        $res = $this->request("api/travel/openContract/contractStatus/{$params['companyid']}/{$params['companytype']}/{$params['parentid']}", self::GET, [], 'contract')->result(); 
        if($res && $res['status']==2){
            //合同开通，判断合同数（国内、境外）
//            $result = $this->request("api/travel/contractbuyers/{$companyId}/number", self::GET, [], "contract")->result();
            $data = ['status' => 1];  
//            if($linecategory == 2){
//                $result['jwSize'] && $data = ['status' => 1 ];
//            }else{
//                $result['gnSize'] && $data = ['status' => 1 ];
//            }
        }else{
            //合同未开通，给予提示
            $data = ['status' => 2];  
        }
        echo json_encode($data);die;
}
    
    public function _getContract($param) {
        foreach ($param as $k => $v) {
            // && $v['gotime']>time()
            if($v['state']==1){
                $result = $this->request("api/travel/manageContract/getContracts/{$v['id']}", self::GET, [], "contract")->result();
                $ares = $this->request("api/travel/manageContract/getGuests/{$v['id']}", self::GET, [], "contract")->result();
                $param[$k]['contractstatus'] = !$ares?3:0 ;//合同填满
                $param[$k]['contract'] = $result;
            }else{
                $param[$k]['contract'] = [];
                $param[$k]['contractstatus']= 0;
            }
        }
        return $param;
    }
    
    //判断保险是否支付
    public function action_ajaxIsPayInsurance() {
        $orderId = Filter::int('orderId');
        $res = $this->request("api/insurance/order/check/count", self::ADD, ["orderId"=>$orderId], "insurance")->get();
        $res['code'] == 200 && $res['result'] && $data = $res['result'];
        echo json_encode($data);die;
    }
    
    //redis 验证是否操作同一团期订单的并发
    protected  function checkRedisOrder($line){
        $redis = RedisDB::getRedis();
        if($redis){
            $red_cache_lineid_time = $redis->get('red_cache_lineid'.$line['dateid']);
            if($red_cache_lineid_time > 0){
                $this->showMessage('线路相关订单已被修改,请重新操作！', $_SERVER['REQUEST_URI']);
            }
            $redis->set('red_cache_lineid'.$line['dateid'],$line['dateid'],2);
        }
    }
    
    protected function _lineDetail($dateid = 0) {
        $aLine = $this->request('line/index', self::GET, array('dateid' => $dateid, 'type' => 'orderLineDetail'), 'line')->result();
        $aLine = (array) $aLine['detail'];
        if ($aLine) {
            $day = $aLine['days'] - 1;
            $aLine['backday'] = date("Y-m-d", strtotime(" +{$day} day", $aLine['gotime']));
        }

        $aLine['shengyu'] = $aLine['surplus']; //$aLine['planid'] > 0 ? $aLine['leaveseats'] : $aLine['person'] - $aLine['personorder'];
        return $aLine;
    }

    
        //判断专车
    protected function _getTakeFleet($param){
        foreach ($param as $k => $v) {
            $takefleetinfo = [
                'companyId' => $this->user['companyinfo']['id'],
                'lineOrderId' => $v['id'],
                'lineOrderCode' => $v['orderid']
            ];
            $takecar = $this->jrequest('/api/take/new/orders/totalamount', self::GET, $takefleetinfo, 'fleet')->result();
            if(!empty($takecar)){
                foreach ((array)$takecar as $key => $val) {
                    $totalprice += $val['total_amount'];
                }
                $param[$k]['takefleetprice'] = $totalprice;
                $param[$k]['istakefleetinfo'] = 1;
            }
        };
        return $param;
    }

    //判读信诚支付是否逾期
    public function _getXincheng($params){
        foreach ($params as $k=>$v){
            if($v['endpaytime'] <1 && $v['stageconfirm'] == 2 && $v['stageday'] > 0 && $v['ispay'] ==1 && ($v['paytime']+$v['stageday']*24*3600)<time()){
                $result = $this->jrequest('/credtrip/trade/'.$this->user['companyinfo']['id'].'/'.$v['orderid'],self::GET,'','pays')->get();
                if($result['code'] == 200 && $result['result']['retCode'] == 'SUCCESS'){
                    $params[$k]['repayAmount'] = ((int)$result['result']['repayAmount']/100);
                }
            }
        }
        return $params;
    }
    //判断供应商是否有可用积分
    protected function _initPoint($params){
        $res =0;
        $result = $this->request("api/company/integral/{$params['companyid']}", self::GET, [], "member")->result();
        if (! $result || (empty($result['canuse'])&& !isset($result['canuse']))) return $res;
        return -$result['canuse'];
    }
    
    //获取线路的营销活动
    protected function _checkpromotion($params){
        $res = 0;
        $result = $this->request("api/promotion/seller/activity/line/{$params['sellerId']}/{$params['lineId']}", self::GET, [], "promotion")->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res = isset($result['result']['activityId']) && $result['result']['activityId'] ? 1 : 0;
        }
        return $res;
    }
    
    
     protected  function _sendSms($mobile, $content){
        $params = array(
            'mobile' => $mobile,
            'content' => $content,
        );
        $this->jrequest('send', self::ADD, $params , 'sms'); 
    }

    //添加鸿运发票地址
    public function action_ajaxyinvoaddr(){
        $params = array(
            'companyId' => $this->user['companyinfo']['id'],
            'name' => Filter::str('name'),
            'mobile' => Filter::str('mobile'),
            'address' => Filter::str('address'),
            'zipCode'=> Filter::str('num'),
        );
        $result = Controller_Uc_Orderservice::addinvoaddr($params,$this);
        echo json_encode(['status' => ($result ? 1: 0) , 'message' => ($result ? '保存成功' : '保存失败，请重新操作！') ]);exit;
    }
    
}
