<?php

/*
 * wangzhen
 * 2016-06-15
 * 订单相关调用服务操作
 */

class Controller_Admin_Orderservice {
    
    //鸿运发票地址添加
    static function addinvoaddr($params, $controller) {
        $res = 0;
        $result = $controller->jrequest('api/addr/addInvoiceExpressAddress', Request::POST, $params, 'hyinvoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }   
    
    //鸿运发票地址列表
    static function getinvoaddr($params, $controller) {
        $res = [];
        $result = $controller->jrequest('api/addr/queryInvoiceExpressAddress/'.$params['companyid'], Request::GET, [], 'hyinvoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    } 
    
    
    //鸿运发票规则
    static function gethyinvorule($params, $controller){
        $res = $res1 = [];
	$res =['status' => 0, 'title' => ''];
        if(isset($params['flag'])){
             $res =['status' => 0, 'title' => ''];
         }
        $result = $controller->jrequest('api/invoice/queryInvoicesDate/'.$params['companyids'], Request::GET, $params, 'member')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
           if(isset($params['flag'])){
               $res =  $result['result'] ? $result['result'][$params['companyids']] : [];
           }else{
               $res =  $result['result'] ? $result['result'] : [];
           } 
        }
        if(isset($params['flag'])){
            $res2 = Controller_Uc_Orderservice::checkppapi(['buycompanyid' => $params['buycompanyid']],$controller);
            $res3 =  Controller_Uc_Orderservice::checkzpapi(['buycompanyid' => $params['buycompanyid']],$controller);
            $res1 = array_merge($res2,$res3);
            if($res3['chkzp']){
                //专票信息
                $res1['zpinfo'] =  Controller_Uc_Orderservice::getzpapi($params, $controller);
            }
         }
         return isset($params['flag']) ? array_merge($res,$res1) : $res;
    }
    
    //发票信息 
    static function getbuyerinvoinfo($params, $controller){
        $res = [];
        $result = $controller->jrequest('api/invoice/queryCompanyInvoiceAccount/'.$params['companyid'], Request::GET, [], 'member')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //申领发票
    static function addinvo($params, $controller){
        $res = [];
        $result = $controller->jrequest('api/invoices', Request::POST, $params, 'hyinvoice')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    
    //check订单是否开过发票
    static function checkorderinvo($params, $controller){
        $res = [];
        $result = $controller->jrequest('api/invoices/isExist', Request::POST, $params, 'hyinvoice')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res1 = $result['result'] ? json_decode($result['result'],true) : [];
            $orderids =  Arr::pluck($res1, 'orderId');
            $res =  array_combine($orderids, $res1);
        }
        return $res;
    }
    
    //更新订单发票状态
    static function upinvostat($params, $controller){
        $res = 0;
        $result = $controller->jrequest('order/index', Request::POST, $params, 'order')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res = 1;
        }
        return $res;
    }
    
    
      //取消发票服务
    static function cancelinvo($params, $controller){
        $res = 1;
        $result = $controller->jrequest('api/invoices/status', Request::POST, $params, 'hyinvoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = 1;
        }
        $res && self::upinvostat(['id' => $params['orderId'] , 'status' => 0,'type' => 'editinvo'],$controller);
        return $res;
    }
    
    //添加发票验证公司信息
    static function checkcompanyinfo($params, $controller){
        return array_merge(Controller_Uc_Orderservice::checkppapi($params, $controller),Controller_Uc_Orderservice::checkzpapi($params, $controller));
    }
    
    //专票是否审核的接口
    static function checkzpinvo($params, $controller){
        $res = 0;
        return $res;
    }
    
    //发票地址删除
    static function delinvoaddr($params, $controller){
        $res = 0;
        $result = $controller->jrequest('api/addr/deleteInvoiceExpressAddress', Request::POST, $params, 'hyinvoice')->get(); 
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //获得专票信息的接口
    static function getzpapi($params, $controller){
        $res = [];
        $result = $controller->jrequest("api/invoice/queryCompanyInvoiceAccount/{$params['buycompanyid']}", Request::GET, [], 'member')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res = $result['result'];
        }
        return $res;
    }
    
    //验证普票审核的接口
    static function checkppapi($params, $controller){
        $res = ['chksealpath' => 0];
        $result = $controller->jrequest("api/invoice/queryCompany/{$params['buycompanyid']}", Request::GET, [], 'member')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res['chksealpath'] = $result['result']['sealStatus'] == 1 ? 1 : 0;
        }
        return $res;
    }
    
    //验证专票审核的接口
    static function checkzpapi($params, $controller){
        $res = ['chkzp' => 0];
        $result = $controller->jrequest("api/invoice/queryCompanyInvoiceAccount/{$params['buycompanyid']}", Request::GET, [], 'member')->get();
        if(isset($result['code']) && $result['code'] == 200){
            $res['chkzp'] = $result['result']['status'] == 1 ? 1 : 0;
        }
        return $res;
    }
    
    

}
