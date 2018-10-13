<?php

/*
 * 订单数据处理类
 * @author:   wangzhen
 */

class Data {

    //订单预订表单处理
    public static function _bookPostArray($user,$platform) {
        $take = Filter::str('taketitle');
        $atake = explode(',', $take);
        $ordersite = Kohana::$config->load('common.websitevalue');
        $params = array(
            'buyerid' => $user['orderinfo']['id'], //计调编号
            'buyercompanyid' => $user['orderinfo']['companyid'], //旅行社
            'detail' => Filter::str('detail'), //订单备注
            'fax' => Filter::str('fax'), //联系人传真
            'mobile' => Filter::str('mobile'), //联系人手机
            'contactname' => Filter::str('contactname'), //联系人姓名
            'tel' => Filter::str('tel'), //联系人电话
            'adult' => Filter::int('adult'), //成人
            'baby' => Filter::int('baby'), //婴儿
            'child' => Filter::int('child'), //小孩
            'hongbaoprice' => Filter::int('hongbaoprice', 0), //抵扣红包
            'hongbaoids' => Filter::str('hongbaoids', 0), //红包id
            'takeid' => $atake[0] ? $atake[0] : '0', //接送id
            'taketype' => $atake[1] ? $atake[1] : 0, //接送类型
            'takeprice' => $atake[2] ? $atake[2] : 0, //接送价格
            'ordersite' => $ordersite[$platform], //下单平台(用户在哪个平台下单),1馨·驰誉,2馨·欢途,4美程
            'selleropeartorid' => Filter::int('selleropeartorid'),//供应商计调
        );
        $danFangSum = Filter::str('singleroomSum');
        if($danFangSum>0){
            foreach(Filter::strArr('title') as $key =>$value){
                if($danFangSum >= $key+1){
                    $singleSum[]=1;
                }else{
                    $singleSum[] =0;
                }
            }
        }
        $guestinfo = array(
            'detail' => Filter::strArr('guestdetail'), //备注
            'singleroom' => $singleSum, //单房差标志
            'idcard' => Filter::strArr('idcard'), //证件号
            'mobile' => Filter::strArr('guestmobile'), //手机
            'title' => Filter::strArr('title'), //姓名
            'gender' => Filter::intArr('gender'), //性别
            'category' => Filter::intArr('category'), //类型
            'cardcategory' => Filter::intArr('cardcategory'), //证件类型
            'idcardurl' => Filter::strArr('idcardUpload'), //证件号照片
            'pinyintitle' => Filter::strArr('pinyintitle'), //姓名拼音
            'birthdate' => Filter::strArr('birthdate'), //出生日期
            'birthplace' => Filter::strArr('birthplace'), //出生地
            'issueplace' => Filter::strArr('issueplace'), //签发地
            'issuedate' => Filter::strArr('issuedate'), //签发日期
            'effectivedate' => Filter::strArr('effectivedate'), //有效日期
        );
        $countCategory = array_count_values(($guestinfo['category']? :[]));
        $params['adult'] = Arr::get($countCategory, 0, 0);
        $params['child'] = Arr::get($countCategory, 1, 0);
        $params['baby'] = Arr::get($countCategory, 2, 0);
        
        return [$params, $guestinfo];
    }

    //订单修提交的参数接受
    public static function _orderEditArray() {
        $take = Filter::str('taketitle');
        $atake = explode(',', $take);
        $params = array(
            'id' => Filter::int('id'),
            'detail' => Filter::str('detail'), //订单备注
            'fax' => Filter::str('fax'), //联系人传真
            'mobile' => Filter::str('mobile'), //联系人手机
            'contactname' => Filter::str('contactname'), //联系人姓名
            'tel' => Filter::str('tel'), //联系人电话
            'state' => Filter::int('state'), //订单状态
            'hongbaoprice' => Filter::int('hongbaoprice', 0), //抵扣红包
            'hongbaoids' => Filter::str('hongbaoids', 0), //红包id
            'takeid' => $atake[0] ? $atake[0] : '0', //接送id
            'taketype' => $atake[1] ? $atake[1] : 0, //接送类型
            'takeprice' => $atake[2] ? $atake[2] : 0, //接送价格
            'tianchi' => Filter::int('tianchi',0), //是否天驰
            'selleropeartorid' => Filter::int('selleropeartorid'),//供应商计调
        );
        $danFangSum = Filter::str('singleroomSum');
        if($danFangSum>0){
            foreach(Filter::strArr('title') as $key =>$value){
                if($danFangSum >= $key+1){
                    $singleSum[]=1;
                }else{
                    $singleSum[] =0;
                }
            }
        }
        $guestinfo = array(
            'id' => Filter::intArr('guestid'), //客户id
            'detail' => Filter::strArr('guestdetail'), //备注
            'singleroom' => $singleSum, //单房差标志
            'idcard' => Filter::strArr('idcard'), //证件号
            'mobile' => Filter::strArr('guestmobile'), //手机
            'title' => Filter::strArr('title'), //姓名
            'gender' => Filter::intArr('gender'), //性别
            'category' => Filter::intArr('category'), //类型
            'cardcategory' => Filter::intArr('cardcategory'), //证件类型
            'idcardurl' => Filter::strArr('idcardUpload'), //证件号照片
            'pinyintitle' => Filter::strArr('pinyintitle'), //姓名拼音
            'birthdate' => Filter::strArr('birthdate'), //出生日期
            'birthplace' => Filter::strArr('birthplace'), //出生地
            'issueplace' => Filter::strArr('issueplace'), //签发地
            'issuedate' => Filter::strArr('issuedate'), //签发日期
            'effectivedate' => Filter::strArr('effectivedate'), //有效日期
        );
        return [$params, $guestinfo];
    }
    
    //详情输出处理
   public static function _setOrderDetailshow(&$oOrder, &$oOrderGuestList, $line) {
        $oOrder['gotraffic'] = Common::dealSellerTract(($oOrder['gotraffic'] ? $oOrder['gotraffic'] :$line['gotraffic']));
        $oOrder['backtraffic'] = Common::dealSellerTract(($oOrder['backtraffic'] ? $oOrder['backtraffic'] : $line['backtraffic']));
        $oOrder['surplus'] =  $line['surplus']; //$line['planid'] ? $line['leaveseats'] : ($line['person'] - $line['personorder']);
        $sexarr = Kohana::$config->load('common.sexarr');
        $typearr = Kohana::$config->load('common.typearr');
        $fangarr = Kohana::$config->load('common.fangarr');
        $cardcarr = Kohana::$config->load('common.cardcategory');
        if(!$oOrderGuestList) return ;
        foreach ($oOrderGuestList as &$v) {
            $v['sexop'] = Common::arrayToSelect($sexarr, $v['gender'], false);
            $v['typeop'] = $typearr[$v['category']];
            if ($v['category'] != 2) {
                $df = $v['singleroom'] > 0 ? 1 : 0;
                $v['fangop'] = Common::arrayToSelect($fangarr, $df, false);
            } else {
                $v['fangop'] = '<option value="0" rtype="1">否</option>';
            }
            $v['cardcategoryop'] = Common::arrayToSelect($cardcarr, $v['cardcategory'], false);
        }
    }
    
    
     //发票数据的处理
    public static function _bookPostInvoice($user,$other){
        $params1 = [];
        $params = array(
            'type' => 'invoice',    //中康发票
            'orderid' => 0,//订单id
            'companyid' => $user['orderinfo']['companyid'],//公司id
            'companyname' => Filter::str('invoicetitle'),//公司名称
            'typeid' => Filter::int('invoicetype'),//发票种类id
            'addrid' => Filter::int('invoiceaddid'),//地址id
            'memberid' => $user['orderinfo']['id'],//操作人id
            'membername' => $user['orderinfo']['realname'],//操作人姓名
            'checkinvoiceid' => Filter::int('invoice',0), //鸿运开发票标识id
            'expressAddressId' => Filter::int('hyexpress'),  //鸿运发票地址id
            'invoiceCategory' => Filter::int('addedtax'), // 1 普票，2 专票 
            'buyerCompanyId' => $user['orderinfo']['companyid'],
            'invoiceTitle' => Filter::str('invoicetitle'),//$user['companyinfo']['companyname'],//发票抬头
            'invoiceType' => Filter::int('hyheader'),//发票内容
            'operationId' => $user['orderinfo']['id'],
            'operationName' => $user['orderinfo']['realname'],
            'hyinvoflg' => Filter::int('hyinvoflg'),//普票与专票的标示
        );
        if($params['invoiceCategory'] == 1){
            $params1 = array(
                'hyinvoflg' => Filter::int('hyinvoflg'),
                'invoiceType' => Filter::int('hyheader'),
                'expressAddressId' => Filter::int('hyexpress'),  
            );
        }elseif($params['invoiceCategory'] == 2){
            $params1 = array(
                'hyinvoflg' => Filter::int('hyinvoflg1'),
                'invoiceType' => Filter::int('hyheader1'),
                'expressAddressId' => Filter::int('hyexpress1'),  
            );  
        }
        
        return array_merge($params,$params1);
    }
    

}
