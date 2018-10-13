<?php

class Controller_Admin_Print extends Controller_Admin_Orderbase {
	
	public $usersession;
    public function before() {
        parent::before();
        $this->usersession = $this->session->get('TRIPB2BCOM_USER');
    }

    //行程导出
    public function action_index() {
//        $this->getCoins($this->usersession['memberinfo']['id'],'109',$this->usersession['memberinfo']['username']);
        $linedateid = Filter::int('dateid');
        $areaid= $this->usersession['companyinfo']['id']?$this->usersession['companyinfo']['id']:Filter::int('areaid');
        $params=array(
            'dateid' => $linedateid,
            'type' => 'dateDetail',
            'takearea'=>$this->_userarea($areaid),
        );
        $linedetail = $this->request('line', self::GET, $params, 'line')->result();
        if(Filter::int('tianchi')){
            $takeinfo = 1;
            $takeinfos = $this->request('api/take/tianchi/getTakeByTianchiId/', self::GET, array('tianchiId' => Filter::int('tianchi'),'lineId'=>$linedetail['detail']['lineid']), 'fleet')->result();
            $data = json_decode($takeinfos['data'],true);
            $takeprice['takeinfo']['id'] =  $data['ID'];
            $takeprice['takeinfo']['title'] =  $takeinfos['townName'].'-'.$data['LocationAddress'];
            $takeprice['takeinfo']['detail'] =  $takeinfos['stationName'];
        }elseif(Filter::int('takeid')){
            $takeinfo = 1;
            $takeprice = $this->request('line', self::GET, ['dateid' => $linedateid,'takeid'=>Filter::int('takeid'),'type' => 'dateDetail'], 'line')->result();
            $takeprice = $takeprice['detail'];
        }
        
        $companyid = $this->usersession['companyinfo']['id'] ? $this->usersession['companyinfo']['id'] : Filter::int('companyid');
        $user = $this->jrequest('api/company/info/'.$companyid, self::GET,[],'member')->result();
        $isdowm = Filter::int('isdown', 0);
        $linedetail['detail']['gotraffic'] = Common::dealSellerTract($linedetail['detail']['gotraffic']);
        $linedetail['detail']['backtraffic'] = Common::dealSellerTract($linedetail['detail']['backtraffic']);
        if ($isdowm) {
            $title = $linedetail['detail']['title'];
            $url = $this->request->uri() . '?dateid=' . $linedateid . '&companyid=' . $companyid . '&areaid='.$areaid.'&takeid='.Filter::int('takeid');
            //$url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $url;
            // $url .= '?dateid=' . $linedateid . '&companyid=' . $companyid;
	    $html = Wordemail::html($url);
            $reminder = explode(',', Filter::str('reminder'));
            $this->htmlroute($reminder, $html);
            $html = preg_replace('#操作人：#','操作人：'.Filter::str('operation'),$html);
            Wordemail::instance()->downfile($title, $html);
        }
        $linedetail =$this->pdfsubstr($linedetail);    
        if(Filter::int('isPdf')){
            $title = $linedetail['detail']['title'];
            $url = $this->request->uri() . '?dateid=' . $linedateid . '&companyid=' . $companyid . '&areaid='.$areaid.'&takeid='.Filter::int('takeid').'&pdf=1';
	    $html = Wordemail::html($url);
            $reminder = explode(',', Filter::str('reminder'));
            $this->htmlroute($reminder, $html);
            $html = preg_replace('#操作人：#','操作人：'.Filter::str('operation'),$html);
            Downword::downMpdf($title, $html);
        }
        $this->view = array(
            'linedetail' => $linedetail['detail'],
            'usersession' => $this->usersession,
            'users'=>$user,
            'operation' =>$this->usersession['memberinfo']['realname'],
            'areaid'=> $areaid,
            'takeinfo'=>$takeinfo,
            'takeprice'=>$takeprice,
        );
    }
    
    protected function mb_chunk_split($string, $length=60, $end ='<br>', $once = false){
        $array = array();
        $strlen = mb_strlen($string);
        while($strlen){
            $array[] = mb_substr($string, 0, $length, "utf-8");  
            if($once)  
                return $array[0] . $end;  
            $string = mb_substr($string, $length, $strlen, "utf-8");  
            $strlen = mb_strlen($string);  
        }  
        return implode($end, $array);  
    }
    
    protected function pdfsubstr($linedetail){
        if(Filter::int('pdf')){
            $linedetail['detail']['include'] = $this->mb_chunk_split($linedetail['detail']['include']);
            $linedetail['detail']['exclude'] = $this->mb_chunk_split($linedetail['detail']['exclude']);
            $linedetail['detail']['feelist']['selfnotes'] = $this->mb_chunk_split($linedetail['detail']['feelist']['selfnotes']);
            $linedetail['detail']['child'] = $this->mb_chunk_split($linedetail['detail']['child']);
            $linedetail['detail']['shopping'] = $this->mb_chunk_split($linedetail['detail']['shopping']);
            $linedetail['detail']['selffinance'] = $this->mb_chunk_split($linedetail['detail']['selffinance']);
            $linedetail['detail']['attention'] = $this->mb_chunk_split($linedetail['detail']['attention']);
            $linedetail['detail']['standard'] = $this->mb_chunk_split($linedetail['detail']['standard']);
            $linedetail['detail']['other'] = $this->mb_chunk_split($linedetail['detail']['other']);
            $linedetail['detail']['planenote'] = $this->mb_chunk_split($linedetail['detail']['planenote']);
            $linedetail['detail']['groupnote'] = $this->mb_chunk_split($linedetail['detail']['groupnote']);
            $linedetail['detail']['ordernote'] = $this->mb_chunk_split($linedetail['detail']['ordernote']);
            $linedetail['detail']['breakrule'] = $this->mb_chunk_split($linedetail['detail']['breakrule']);
            $linedetail['detail']['reminder'] = $this->mb_chunk_split($linedetail['detail']['reminder']);
            foreach((array)$linedetail['detail']['routelist'] as $key =>$value){
                if($linedetail['detail']['isstruct']==2){
                    if($value['structlist']){
                        foreach((array)$value['structlist'] as $k => $v){
                            $linedetail['detail']['routelist'][$key]['structlist'][$k]['notes'] = $this->mb_chunk_split($v['notes']);
                        }
                    }
                }else{
                    $linedetail['detail']['routelist'][$key]['detail'] = $this->mb_chunk_split($value['detail']);
                    
                }
            }
        }
        return $linedetail;
    }

    //名单导出
    public function action_guest() {
  //      $this->getCoins($this->usersession['memberinfo']['id'],110,$this->usersession['memberinfo']['username']);//挖金币调用 
        $linedateid = Filter::int('dateid');
        $orderid = Filter::int('id');
        $params=array(
            'dateid' => $linedateid,
            'type' => 'dateDetail',
            'takearea'=>$this->_userarea($this->usersession['companyinfo']['id']),
        );
        $linedetail = $this->request('line', self::GET, $params, 'line')->result();
        //订单的基本信息
        $aorder = $this->getOrder($orderid,'order/index');
        $guest = $this->request('order/guest', self::GET, ['orderid' => $orderid], 'order')->result();
        $linedetail['detail']['gotraffic'] = Common::dealSellerTract($aorder['gotraffic'] ? $aorder['gotraffic'] : $linedetail['detail']['gotraffic']);
        $linedetail['detail']['backtraffic'] = Common::dealSellerTract($aorder['backtraffic'] ? $aorder['backtraffic'] : $linedetail['detail']['backtraffic']);
        $linedetail = $this->pdfsubstr($linedetail);
        $isdowm = Filter::int('isdown', 0);
        if ($isdowm) {
            $title = $linedetail['detail']['title'];
            $url = $this->request->uri() . '?dateid=' . Filter::int('dateid') . '&id=' . Filter::str('id');
//            $url = 'http://' . $_SERVER['SERVER_NAME'] . '/' . $this->request->uri();
//            $url .= '?dateid=' . Filter::int('dateid') . '&id=' . Filter::str('id');
            $html = Wordemail::html($url);
            $this->html([], $html);
            $html = preg_replace('#class="usertitle">#', '>' . Filter::str('usertitle'), $html);
            Wordemail::instance()->downfile($title, $html);
        }
        if(Filter::int('isPdf')){
            $title = $linedetail['detail']['title'];
            $url = $this->request->uri() . '?dateid=' . Filter::int('dateid') . '&id=' . Filter::str('id').'&pdf=1';
	    $html = Wordemail::html($url);
            $this->html([], $html);
            Downword::downMpdf($title, $html);
        }
        $this->view = array(
            'linedetail' => $linedetail['detail'],
            'customs' => $guest,
            'usersession' => $this->usersession,
        );
    }
        //行程发邮件
    public function action_routemail() {
        $subject = Filter::str('title');
        $to = Filter::str('email');
        $companyid = Filter::int('companyid');
        $infourl = Filter::str('infourl');
        preg_match('/(?<=&reminder=)(.*)/', $infourl, $matches);
        $reminder = explode(',', $matches[0]);
        $infourl = preg_replace('#&.*#', '', $infourl);
        //修复线上不能发邮件
        $url = substr($infourl,strrpos($infourl,'/')+1)."&companyid={$companyid}";
        $html = Wordemail::html($url);
//        $html = file_get_contents($infourl."&companyid={$companyid}", 5);
        $this->htmlroute($reminder, $html);
//        if (Wordemail::instance()->sendMail(array($to), $subject, $html)) {
        if($this->_sendMail(array($to), $subject, $html)){
            $result = 1;
        } else {
            $result = 0;
        }
        echo $result;
        exit();
    }
    
    //导出出团单
    public function action_single() {

        $orderid = Filter::int('orderid');
        $orderinfo = $this->request('order/index', self::GET, ['orderids' => $orderid,'type' => 'otherSearch'], 'order')->result();
        $temp = current((array)$orderinfo['list']);
        if($orderinfo['list']){
            $linedateid = $temp['lineid'];
        }
        $linedetail = $this->request('line', self::GET, ['dateid' => $linedateid,'type' => 'dateDetail'], 'line')->result();
        $linedetail =$this->pdfsubstr($linedetail);
        $linedetail = $linedetail['detail'];

        if($temp['tianchi']){
            $takeinfos = $this->request('api/take/tianchi/getTakeByTianchiId/', self::GET, array('tianchiId' => $temp['tianchi'],'lineId'=>$linedetail['lineid']), 'fleet')->result();
            $data = json_decode($takeinfos['data'],true);
            $temp['takeinfo']['id'] =  $data['ID'];
            $temp['takeinfo']['title'] =  $takeinfos['townName'].'-'.$data['LocationAddress'];
            $temp['takeinfo']['detail'] =  $takeinfos['stationName'];
            $temp['takeinfo']['price'] = $temp['takeprice'];
        }elseif($temp['takeid']){
              $temp['takeinfo'] = $this->request('line/take', self::GET, ['type' =>'findtitle','id' =>$temp['takeid']],'line')->result();
        }
        $user = (array)$this->jrequest('api/company/info/'.$linedetail['companyid'], self::GET,[],'member')->result();
         //景点和酒店
        if($linedetail['isstruct']==2){
            foreach ($linedetail['routelist'] as $k=>&$v){
                foreach($v['structlist'] as $k1=>&$v1){
                    $v1['targetlist'] = $this->formattarget($v1['targetlist'],$v1['targetlistids'],$v1['tag']);
                }
            }
        }
        $guest = $this->request('order/guest', self::GET, ['orderid' =>[$orderid],'select'=>1], 'order')->result();
        $isdowm = Filter::int('isdown', 0);
        $linedetail['routelist'] = (array)$linedetail['routelist'];
        //$this->getCoins(209);
        $buyer = $this->request('order/guest', self::GET, ['orderid' =>[$orderid]], 'order')->result();
        if($buyer){
            $buyer = (array)$buyer;
            $buyer = current($buyer);
            $buyer = current($buyer);
            $tt = (array)$this->jrequest('api/company/info/'.$buyer['buyercompanyid'], self::GET, $params, 'member')->result();
            $buyerinfo['companyname'] = $tt['companyname'];
            $realname = $this->jrequest('api/member/info/'.$buyer['buyerid'], self::GET, $params, 'member')->result()['realname'];
            if($buyer['contactname']){
                $buyerinfo['realname'] = $buyer['contactname'];
            }else{
                $buyerinfo['realname'] = $realname;
            }
            $buyerinfo['tel'] = $buyer['otel']?:$tt['tel'];
            $buyerinfo['responsiblefax'] = $buyer['ofax']?:$tt['responsiblefax'];
            $buyerinfo['responsiblemobile'] = $buyer['omobile']?:$tt['responsiblemobile'];
        }
        if ($isdowm) {
            $title =$buyerinfo['companyname'].'-'.date('Y-m-d',$linedetail['gotime']);
            //$url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $this->request->uri();
            //$url .= '?orderid='.Filter::int('orderid');
	    $url .= $this->request->uri().'?orderid='.Filter::int('orderid');
	    $html = Wordemail::html($url);
            //$html = Wordemail::instance()->tohtml($url);
            $this->html([], $html);
            Wordemail::instance()->downfile($title, $html);
        }
        if(Filter::int('isPdf')){
            $title =$buyerinfo['companyname'].'-'.date('Y-m-d',$linedetail['gotime']);
            $url .= $this->request->uri().'?orderid='.Filter::int('orderid').'&pdf=1';
	    $html = Wordemail::html($url);
            $this->html([], $html);
            Downword::downMpdf($title, $html);
        }
        $this->view = array(
            'buyerinfo'=>$buyerinfo,
            'orderinfo'=>$temp,
            'linedetail'=> $linedetail,
            'guestlist' => $this->formatguest($guest),
        );
    }
    
    //出团单发邮件
    public function action_singlemail() {
        $to = Filter::str('email');
        $subject = Filter::str('title');
        $infourl = Filter::str('infourl');
        $infourl = preg_replace('#&isdown=1.*#', '', $infourl);
        $html = Wordemail::instance()->tohtml($infourl);
        $this->html([], $html);
        if($this->_sendMail(array($to), $subject, $html)){
            $result = 1;
        } else {
            $result = 0;
        }
        echo $result;
        exit();  
    }
    
    //发送邮件
    protected function _sendMail(array $to, $title, $content){
        $params = array(
            'to' =>  $to,
            'title' => $title,
            'content' => $content,
        );
        $result = $this->jrequest('send', self::ADD, $params, 'mail')->get();
        return $result['code'] == 200 ? 1 : 0;
    }
    
    //格式化前往  2是与 1是或
    private function formattarget($target) {
        $res = $target;
        foreach ($target as $k=>$v){
            if($k%2!=0){
                if($v==1){
                    $res[$k]='或';
                }else{
                    $res[$k]='与';
                }
            }else{
                $res[$k] = "<span class ='blue'>".$v."</span>";
            }
        }
        return $res;
    }
    
    //格式化游客数据
    private function formatguest($arr) {
        $adult = $child = $baby = $totalprice = 0;
        foreach ((array) $arr as $k => $v) {
            if ($v) {
                foreach ($v as $k1 => $v1) {
                    switch ($v1['category']) {
                        case 0:
                            $adult+=1;
                            break;
                        case 1:
                            $child+=1;
                            break;
                        case 2:
                            $baby+=1;
                            break;
                    }
                    $totalprice+=$v1['price'];
                    $temp = (array)$this->jrequest('api/company/info/'.$v1['buyercompanyid'], self::GET, $params, 'member')->result();
                    $v1['companyname'] = $temp['companyname'];
                    $v1['tel'] = $temp['tel'];
                    $v1['responsiblefax'] = $temp['responsiblefax'];
                    $v1['responsiblemobile'] = $temp['responsiblemobile'];
                    $v1['realname'] = $this->jrequest('api/member/info/'.$v1['buyerid'], self::GET, $params, 'member')->result()['realname'];
                    $res['detail'][] = $v1;
                }
            }
        }
        $res['total']['adult'] = $adult;
        $res['total']['child'] = $child;
        $res['total']['baby'] = $baby;
        $res['total']['totalprice'] = $totalprice;
        return $res;
    }

    private function html($reminder, &$html) {
        if ($reminder) {
            if ($reminder[0] == 1) {
                $html = preg_replace('#<table class="baseinfo"([\s\S]*?)<\/table>#', '', $html);
            }
            if ($reminder[1] == 1) {
                $html = preg_replace('#<table class="routeinfo"([\s\S]*?)<\/table>#', '', $html);
            }
            if ($reminder[2] == 1) {
                $html = preg_replace('#<table class="reminder"([\s\S]*?)<\/table>#', '', $html);
            }
            if ($reminder[3] == 1) {
                $html = preg_replace('#<tr class="include">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[4] == 1) {
                $html = preg_replace('#<tr class="exclude">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[5] == 1) {
                $html = preg_replace('#<tr class="child">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[6] == 1) {
                $html = preg_replace('#<tr class="shopping">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[7] == 1) {
                $html = preg_replace('#<tr class="kxxm">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[8] == 1) {
                $html = preg_replace('#<tr class="present">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[9] == 1) {
                $html = preg_replace('#<tr class="attention">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[10] == 1) {
                $html = preg_replace('#<tr class="other">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[11] == 1) {
                $html = preg_replace('#<tr class="applicationnote">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[12] == 1) {
                $html = preg_replace('#<tr class="detail">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[13] == 1) {
                $html = preg_replace('#<tr class="faq">([\s\S]*?)<\/tr>#', '', $html);
            }
        }

        $html = preg_replace('#<div class="selectdy">([\s\S]*?)<\/div>#','', $html);
        $html = preg_replace('#<div class="line_print_box">([\s\S]*?)<\/div>#', '', $html);
    }
    
    private function htmlroute($reminder, &$html) {
        if ($reminder) {
            if ($reminder[0] == 1) {
                $html = preg_replace('#<table class="baseinfo"([\s\S]*?)<\/table>#', '', $html);
            }
            if ($reminder[1] == 1) {
                $html = preg_replace('#<table class="routeinfo"([\s\S]*?)<\/table>#', '', $html);
            }
            if ($reminder[2] == 1) {
                $html = preg_replace('#<table class="reminder"([\s\S]*?)<\/table>#', '', $html);
            }
            if ($reminder[3] == 1) {
                $html = preg_replace('#<tr class="include">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[4] == 1) {
                $html = preg_replace('#<tr class="exclude">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[5] == 1) {
                $html = preg_replace('#<tr class="child">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[6] == 1) {
                $html = preg_replace('#<tr class="shopping">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[7] == 1) {
                $html = preg_replace('#<tr class="kxxm">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[8] == 1) {
                $html = preg_replace('#<tr class="attention">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[9] == 1) {
                $html = preg_replace('#<tr class="standard">([\s\S]*?)<\/tr>#', '', $html);
            }
//            if ($reminder[10] == 1) {
//                $html = preg_replace('#<tr class="applicationnote">([\s\S]*?)<\/tr>#', '', $html);
//            }
            if ($reminder[10] == 1) {
                $html = preg_replace('#<tr class="other">([\s\S]*?)<\/tr>#', '', $html);
            }
            if ($reminder[11] == 1) {
                $html = preg_replace('#<tr class="faq">([\s\S]*?)<\/tr>#', '', $html);
            }
        }
        $html = preg_replace('#<div class="selectdy">([\s\S]*?)<\/div>#','', $html);
        $html = preg_replace('#<div class="line_print_box">([\s\S]*?)<\/div>#', '', $html);
    }
    
        //生成确认单
    public function action_confirm(){
        $this->_generateOrder();
    }


    private function _generateOrder(){
        session_start();
        $this->session = Session::instance();
        $userinfo = $this->session->get('TRIPB2BCOM_USER');
        $orderid = Filter::int('orderid');
        $orderinfo = $this->request('order/index', self::GET, ['orderid' => $orderid,'type' => 'printconfirm'], 'order')->result();

        $linedateid = $orderinfo['orderinfo']['lineid'];
        if($orderinfo['orderinfo']['takeid']){
           $orderinfo['orderinfo']['takeinfo'] = $this->request('line/take', self::GET, ['type' =>'findtitle','id' =>$orderinfo['orderinfo']['takeid']],'line')->result();
        }
        $linedetail = $this->request('line', self::GET, ['dateid' => $linedateid,'type' => 'sellerLineDetail'],'line')->result();
        $linedetail = $this->pdfsubstr($linedetail);
        
        $linedetail = $linedetail['detail'];
        $linedetail['routelist'] = (array)$linedetail['routelist'];
        $user = (array)$this->jrequest('api/company/info/'.$linedetail['companyid'], self::GET,[],'member')->result();
        //景点和酒店
        if($linedetail['isstruct']==2){
            foreach ($linedetail['routelist'] as $k=>&$v){
                foreach($v['structlist'] as $k1=>&$v1){
                    $v1['targetlist'] = $this->formattarget($v1['targetlist']);
                }
            }
        }
        $this->guest($orderinfo['list'],$orderinfo);
        $isdowm = Filter::int('isdown', 0);
        $buyer = $orderinfo['orderinfo'];
        $tt = (array)$this->jrequest('api/company/info/'.$buyer['buyercompanyid'], self::GET, $params, 'member')->result();
        $buyerinfo['companyname'] = $tt['companyname'];
        $realname = $this->jrequest('api/member/info/'.$buyer['buyerid'], self::GET, $params, 'member')->result()['realname'];
        if($buyer['contactname']){
            $buyerinfo['realname'] = $buyer['contactname'];
        }else{
            $buyerinfo['realname'] = $realname;
        }
        $buyerinfo['tel'] = $buyer['tel']?:$tt['tel'];
        $buyerinfo['responsiblefax'] = $buyer['fax']?:$tt['responsiblefax'];
        $buyerinfo['responsiblemobile'] = $buyer['mobile']?:$tt['responsiblemobile'];
        if ($isdowm) {
            $title = $buyerinfo['companyname'] . "团号【".$linedetail['groupnumber']."】".date('Y-m-d',$linedetail['gotime'])."出团行程确认单";
            $url =  $this->request->uri().'?orderid='.Filter::int('orderid');
            $html = Wordemail::html($url);
            $reminder = explode(',', Filter::str('reminder'));
            $this->html($reminder, $html);
            if($userinfo['memberinfo']['tel']){
                 $html = preg_replace('#电话：.*<br/>#','电话：<br/>',$html);
                 $html = preg_replace('#电话：#','电话：'.Filter::str('tel'),$html);
            }
            if($userinfo['memberinfo']['fax']){
                $html = preg_replace('#传真：.*<br/>#','传真：<br/>',$html);
                $html = preg_replace('#传真：#','传真：'.Filter::str('fax'),$html);
            }
            Wordemail::instance()->downfile($title, $html);
        }
        if(Filter::int('isPdf')){
            $title =$buyerinfo['companyname'] . "团号【".$linedetail['groupnumber']."】".date('Y-m-d',$linedetail['gotime'])."出团行程确认单";
            $url .= $this->request->uri().'?orderid='.Filter::int('orderid').'&pdf=1';
	    $html = Wordemail::html($url);
            $reminder = explode(',', Filter::str('reminder'));
            $this->html($reminder, $html);
            Downword::downMpdf($title, $html);
        }
        $lines = $this->request('line/index', self::GET, ['dateid' => $linedateid, 'type' => 'dateDetail'], 'line')->result();
        $orderinfo['orderinfo']['destplace'] = strpos($lines['detail']['destplace'],'日本') === false ? 0 : 1;

        $this->view = array(
            'buyerinfo'=>$buyerinfo,
            'linedetail'=> $linedetail,
            'orderinfo'=> $orderinfo,
            'users'=>$user,
            'ismc'=>$_SERVER['HTTP_HOST'],
            'tel' =>$userinfo['memberinfo']['tel'],//登入者的电话
            'fax' =>$userinfo['memberinfo']['fax'],//登入者的传真
            'tt' => $tt,    //买家信息
        );
    }
    
    //格式化游客数据new
    private function guest($arr, &$orderinfo) {
        if (is_array($arr)) {
            $adult = $child = $baby = $totalprice = 0;
            foreach ($arr as $k => $v) {
                switch ($v['category']) {
                    case 0:
                        $adult+=1;
                        break;
                    case 1:
                        $child+=1;
                        break;
                    case 2:
                        $baby+=1;
                        break;
                }
                $orderinfo['orderinfo']['adultnum'] = $adult;
                $orderinfo['orderinfo']['childnum'] = $child;
                $orderinfo['orderinfo']['babynum'] = $baby;
            }
        } else {
            
        }
        return;
    }

}
