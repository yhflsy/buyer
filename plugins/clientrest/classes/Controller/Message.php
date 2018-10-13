<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 用法
 * Controller_Message::addOrderMessage($aOrder, $this);   
 */

class Controller_Message {

    /**
     * 添加消息
     * @param type $params
        companyId: 公司Id--必填
        category: 公司类型(1-组团社或批发商;2-地接社)--必填
        orderId: 订单Id--必填
        orderType: 订单状态(1-新订单;2-付款订单;3-买家申请退款订单;4-确认订单;5-卖家取消订单;6-卖家退款订单;7-买家取消订单)--必填
        productType: 产品类型(JD-酒店;JQ-景区;TC-套餐;CT-当地参团)--必填
     * @param type $controller
     */
    static function addOrderMessage($params, $controller) {
        Restful::Async(true);
        $aMsg = ['companyId' => $params['companyId'], 'category' => $params['category'], 'orderId' => $params['orderId'], 'orderType' => $params['orderType'], 'productType' => $params['productType']];
        $aMsg['category'] && $aMsg['orderType'] && $aMsg['productType'] && $aMsg['companyId'] && $aMsg['orderId'] && $controller->jrequest('api/localTravel/orderMessage/create', Request::POST, $aMsg, 'ground');
        Restful::Async(false); 
    }

    /**
     * 删除消息
     * @param type $params
        companyId: 公司Id--必填
        category: 公司类型(1-组团社或批发商;2-地接社)
        orderId: 订单Id--必填
        productType: 产品类型(JD-酒店;JQ-景区;TC-套餐;CT-当地参团)--必填
     * @param type $controller
     */
    static function delOrderMessage($params, $controller) {
        Restful::Async(true);
        $aMsg = ['companyId' => $params['companyId'], 'category' => $params['category'], 'orderId' => $params['orderId'], 'productType' => $params['productType']];
        $aMsg['companyId'] && $aMsg['category'] && $aMsg['productType'] && $aMsg['orderId'] && $controller->jrequest('api/localTravel/orderMessage/delete', Request::POST, $aMsg, 'ground');
        Restful::Async(false); 
    }
    
    /**
     * 获得所有订单消息
     * @param type $params
        companyId: 公司Id--必填
        category: 公司类型(1-组团社或批发商;2-地接社)--必填
        orderType: 订单状态(1-新订单;2-付款订单;3-买家申请退款订单;4-确认订单;5-卖家取消订单;6-卖家退款订单;7-买家取消订单)--必填
        productType: 产品类型(JD-酒店;JQ-景区;TC-套餐;CT-当地参团)--必填
     * @param type $controller
     */
    static function getOrderMessage($params, $controller) {
        $res = [];
        $aMsg = ['companyId' => $params['companyId'], 'category' => $params['category'], 'orderType' => $params['orderType'], 'productType' => $params['productType']];
        $result = $controller->jrequest('api/localTravel/orderMessage/orderIds', Request::POST, $aMsg, 'ground')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res =$result['result'];
        }
        return $res;
    }
    
    /**
     * 获得消息数量
     * @param type $params
       companyId: 公司Id--必填
       category: 公司类型(1-组团社或批发商;2-地接社)--必填
     * @param type $controller
     */
    static function getOrderMessageList($params, $controller) {
        $res = [];
        $aMsg = ['companyId' => $params['companyId'], 'category' => $params['category']];
        $result = $controller->jrequest("api/localTravel/orderMessage/{$params['companyId']}/{$params['category']}", Request::GET, [], 'ground')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res =$result['result'];
        }
        return $res;
    }

}
