<?php
/**
 * Created by PhpStorm.
 * User: Kezj
 * Date: 2018/1/30
 * Time: 15:31
 */

class Controller_Admin_Book extends Controller_Admin_Orderbase
{

    public function before(){
        parent::before();
    }

    //线路预定
    public function action_index() {
        $id = Filter::int('id') or Common::windowClose("抱歉，此线路不存在，请选择其它线路！"); //线路中dataid
        //重复提交验证的token
        $token = time().microtime(true).rand(1000,9999);
        if ($this->request->method() != Request::POST) {
            $this->session->set('ordertoken'.$this->user['memberinfo']['id'],$token);
        }
        //线路基本信息服务
        $aline = $this->getLine($id);//dateid
        $aline || Common::windowClose("抱歉，此线路不存在，请选择其它线路！");
        $aline['departureName'] = json_decode($aline['departure'],true)['city']?:json_decode($aline['departure'],true)['province'];
        $aline['backtime'] = $aline['gotime'] + ($aline['days'] - 1) * 86400;
        $aline['gotraffic'] = Common::dealSellerTract($aline['gotraffic']); //交通的处理
        $aline['backtraffic'] = Common::dealSellerTract($aline['backtraffic']);
        Common::lineState($aline); //线路状态
        $stateinfo = ['正常','停止','客满','截止','删除','过期'];   //0 正常 1停止 2 客满 3截止 4删除 5过期
        if($aline['state']){
            Common::windowClose('该线路已经'.$stateinfo[$aline['state']].'!');
        }
        //接送
        $takeinfo = Filter::int('takeid')  ? $this->getTakeOne(Filter::int('takeid'),$aline['tianchi'],0,$aline['lineid']) : array('detail' => []);
        $takes = $this->getTake(array('lineid'=> $id, 'taketype'=> -1),$aline['tianchi'],$aline['lineid']);
        //check是否有免费保险
        $checkInsurance = $this->checkLineFreeInsurance(array('webSite' =>$this->websitevalue[$this->platform] ,'siteId' => $aline['siteid'], 'lineCategoey' => $aline['linecategory']));
        $freeInsurance = [];
        if($checkInsurance){
            $freeInsurance = $this->lineFreeInsurance(array('webSite' =>$this->websitevalue[$this->platform] ,'siteId' => $aline['siteid'], 'lineCategoey' => $aline['linecategory'], 'days' => $aline['days']));
        }
        //发票状态
        $invoiceInfo = Controller_Admin_Orderservice::getbuyerinvoinfo(array('companyid'=> $this->user['companyinfo']['id']),$this);
        $invoice = [];
        if($invoiceInfo){
            $invoiceInfo['status'] == 0 ? $invoice = ['status'=>0,'msg1'=>'您的开票资质正在审核中。','msg2'=>'查看申请>>'] : ($invoiceInfo['status'] == 2 ? $invoice = ['status'=>2,'msg1'=>'您的开票资质未通过审核。','msg2'=>'重新申请开票>>'] : $invoice = ['status'=>1,'info'=>$invoiceInfo]);
        }else{
            $invoice = ['status'=>'','msg1'=>'您还未申请开通平台发票。','msg2'=>'立即申请开票>>'];
        }
        //目的地取客服
        if ($aline['companyid'] && $aline['cityids']) {
            $city = json_decode($aline['cityids'], 1);
            $city[0]['city'] && $city = $city[0];
            $city[0]['province'] && $city = $city[0];
            if ($aline['linecategory'] == 2) {
                $aline['cityid'] = $city['city'] ? $city['city'] : $city['province'];
            } else {
                $aline['cityid'] = $city['province'] ? $city['province'] : $city['city'];
            }
            $customerinfo = $this->request('system/callcenter', self::GET, array('companyid' => $aline['companyid'], 'cityids' => $aline['cityid'], 'siteid' => $aline['siteid'], 'type' => 'findcallcenter',),'base')->get();
            if ($customerinfo['code'] !== 200) {
                $this->setErrToHeader($customerinfo);
            }
        }
        //活动参与
        $hbparams = ['type' => 1,'companyid' => $this->user['orderinfo']['companyid'],'sellercompanyid' => $aline['companyid']];
        $hongbao = $this->request('hongbao/buyerfinance', self::GET, $hbparams, 'hongbao')->result();
        //提交操作
        $this->_orderBook($aline);
        $this->view = array(
            'token' => $token,
            'aline' => $aline,
            'stateinfo' => $stateinfo,
            'sellerinfo' => $this->getSellerinfo($aline['companyid']), //供应商标题与联系方式
            'customerinfo' => $customerinfo['result'],
            'company' => $this->sellCompanyOpt($aline),
            'checkInsurance' => $checkInsurance,
            'freeInsurance' => $freeInsurance,
            'hongbao' => (array) $hongbao['list'],
            'cardcarr' =>  Kohana::$config->load('common.cardcategory'), //证件类型
            'gotime' => $this->getGoTime($id), //出团日期
            'other' => [
                'descentinfo' => $this->getSellerDesCell(array('type' => 'findqq', 'companyid' => $aline['companyid'], 'siteid' => $aline['siteid'], 'destination' => $aline['destination'])),//目的地联系方式
                'takeinfo' => (array)$takeinfo['detail'],
                'takes' => (array)$takes['list'],
                'invoice' => $invoice, //发票状态
                'hyinvoiceaddr' => Controller_Admin_Orderservice::getinvoaddr(array('companyid'=> $this->user['companyinfo']['id']),$this), //鸿运发票地址
                'checkpromotion' => $this->_checkpromotion(array('sellerId'=> $aline['companyid'],'lineId'=> $aline['lineid'])), //线路活动规则
            ],
            'aorder'=>['taketype'=>$takeinfo['detail']['tag']],
        );
    }

    // 订单预订提交操作
    private function _orderBook($oLine) {
        if ($this->request->method() == Request::POST) {
            $msg = '预订';
            $ispay = $this->jrequest("api/company/info/{$this->user['companyinfo']['id']}", self::GET)->result();
            $ptoken = Filter::str('token');
            if($ptoken && $ptoken != ($this->session->get('ordertoken'.$this->user['memberinfo']['id']))){
                $this->showMessage('订单已提交,请勿重复刷新提交', '/order.html');
            }
            $this->session->set('ordertoken'.$this->user['memberinfo']['id'],FALSE);
            list($params, $guests) = Data::_bookPostArray($this->user,($this->platform ? $this->platform : 'tripb2b'));
            if( $oLine['tianchi'] && $params['takeid']){
                $params['takeprice'] = $this->newTianChiAmt($params['takeid'],$oLine['lineid']);
            }elseif($params['takeid']){
                $aTake = $this->getTakeOne($params['takeid']);
                $params['takeprice'] = $aTake['detail']['price'];
            }
            $invoiceinfo = Data::_bookPostInvoice($this->user, []); //发票
            if(($params['adult'] + $params['child'] + $params['baby'] ) <=0){
                Common::windowClose('人数应大于0,'.$msg .'失败');
            }
            if ($oLine['surplus'] < ($params['adult'] + $params['child']) || $oLine['surplus'] <= 0 || ($params['adult'] + $params['child']) <= 0 ) {//是否有可预订的人判定
                Common::windowClose($msg . '失败');
            }
            $param = array(
                'type' => 'create',
                'dateid' => $oLine['dateid'],
                'otherinfo' => json_encode($params),
                'guestinfo' => json_encode($guests),
                'invoiceinfo' => json_encode($invoiceinfo),
                'instantconfirm' => $oLine['instantconfirm'] ?1: 0, //即时确认
                'ip'=> Ip::GeIpAddress()?:'',
                'flag'=> Filter::int('flag')
            );
            $url = "/order.html";
            $cachename = 'book';
            $result = $this->request('order/index', self::ADD, $param, 'order')->get();
            if($oLine['instantconfirm'] && $result['result']['orderstate'] == 1){
                $aorder = $this->getOrder($result['result']['id'], 'order/index');//订单的基本信息
                $this->addOrderConfirmFinanceLog($aorder);
            }
            if (isset($result['result']['id']) && $result['result']['id'] && $result['code'] == 200) {
                Restful::Async(true); //开启异步
                $this->msg = sprintf(Kohana::$config->load('tip.order.new'), $result['result']['orderinfo']['orderid']);
                //订单日志
                $param = array(
                    'orderid' => $result['result']['id'],
                    'memberid' => $this->user['orderinfo']['id'],
                    'detail' => $this->msg,
                    'readmemberid' => $oLine['memberid'],
                );
                $this->_addOrderLog($param);
                //积分日志服务
                $this->_handleIntegralLog($result, $oLine, $cachename);
                //免费保险创建
                Filter::int('checkinsurance') ? $this->createFreeInsuranOrder($result['result'], $oLine, Filter::int('checkinsurance'),1) : '' ;
                //订单消息
                $params = array(
                    'companyid' => $oLine['companyid'],
                    'orderid' => $result['result']['id'],
                    'type' => 0,
                    'msg' => '新订单',
                );
                $this->addOrderMsg($params);
                //app推送
                $apparams = array(
                    'companyid' => $oLine['companyid'],
                    'orderid' => $result['result']['id'],
                    'msg' => $this->msg,
                );
                $this->addAppMsg($apparams);
                //pc推送
                $pcparams = array(
                    'pusherparentid' => $this->user['orderinfo']['companyid'],
                    'pusherid' => $this->user['orderinfo']['id'],
                    'receiverparentid' => $oLine['companyid'],
                    'receiverid' => $oLine['memberid'],
                    'msg' => '新订单',
                    'optype' => 0,
                    'url' => $result['result']['url'],
                );
                $this->addPcMsg($pcparams);
                if($oLine['instantconfirm'] && $result['result']['orderstate'] == 1 && !Filter::int('saleinsurance') &&  !Filter::int('checkinsurance') && $ispay['ispay'] ==0){
                    $param = array(
                        'orderid' => trim($result['result']['id'], ','),//批量支付时，多个id用，分隔
                        'paystyle' => 4,//支付类型 1=积分订单
                        'website' => $this->websitevalue[$this->platform],//平台1-cy 2-ht， 4-mc
                        'memberid' => $this->user['memberinfo']['id'],//买家Id,
                        'companyid' => $this->user['companyinfo']['id'],//公司id
                        'platform' => $this->platform,//平台
                    );
                    $apiUrl = 'http://' . $this->sitedomain['pay'][$this->platform] . "/buyer?param=" . base64_encode(json_encode($param));
                    header("Location: " . $apiUrl);
                }else{
                    Filter::int('checkinsurance')||Filter::int('saleinsurance') ? $this->showMessage($this->msg . '成功', $url) : $this->goUrl("book.booksuccess.html?act=book&orderid={$result['result']['id']}",1);
                }
            } elseif($result['code']) {
                $this->showErrMessage((isset($result['code']) ? $result['code'] : '') ,$msg.'失败！'.$result['message'], $_SERVER['REQUEST_URI']);
            }else{
                $this->showMessage('服务超时,'.$msg.'失败！', $_SERVER['REQUEST_URI']);
            }
        }
    }

    //预订成功跳转页
    public function action_bookSuccess() {
        $list = $list1 = [];
        $orderid = Filter::int('orderid');
        //订单的基本信息
        $aorder = $this->getOrder($orderid, 'order/index');
        empty($aorder) && Common::windowClose('您访问的数据不存在');
        //线路基本信息服务
        $aline = $this->getLine($aorder['lineid']);

        $params = [
            'companyType'       =>  Filter::int('companytype',1), // 保险公司分类 - 【0 合众；1 平安；2 美亚；3 安盛；4 太平洋人数；5天安人寿；6 史带；7 长生人寿；8 安联；】 必填
            'trafficType'       =>  $aline['traffictool'] == 1 ?  0 : 1,// trafficType:交通工具 - 【0:飞机，1:其他 】
            'category'          => $aline['linecategory'], //线路类型  0 短线；1 长线；2 出境线
            'term'              => $aline['days'],     //行程天数
            'pageSize'          => 15,
        ];
        $result1 = $this->jrequest('api/insurance/listpage/listCompany', self::ADD, $params, 'insurance')->get();
        if(isset($result1['code']) && $result1['code'] == 200){
            $params['companyType'] = $result1['result']['0']['companyType'];
        }
        $result = $this->jrequest('api/insurance/listpage/listfist', self::ADD, $params, 'insurance')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $list = $result['result']['list'];
        }
        $this->view = array(
                 'act'          =>     Filter::str('act'),
                 'list'         =>     $list,
                 'order'        =>     $aorder,
                 'line'         =>     $aline,
                 'category'     =>     Kohana::$config->load('common.premiumtype'),
                 'insurancecompanytype' => Kohana::$config->load('common.insurancecompanytype'),
                 'list1'         =>     $list1,
                );
    }
}