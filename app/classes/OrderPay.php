<?php

/**
 * 订单支付类
 */
class OrderPay {

    static $_instance;
    private $payinfo; // 支付信息
    private $paytype; // 1 支付,2 批量支付,3境外支付,4大社支付
    /**
     * 单例引用
     * @return type
     */
    public static function instance() {
        if (self::$_instance == NULL) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }
    
    /*
     * 订单检测
     */
    public function checkOrder($params){
        if (! $order) {
            $this->error = '订单不存在！';
            return ;
        }

        if ($order['paytime'] > 0 && $order['ispay']) {
            $this->error = '您的订单已付款，请勿重复付款！';
            return ;
        }

        // 判断确认和出票
        if (! in_array($order['state'], [1, 4])) {
            $this->error = sprintf("订单不允许支付状态！[%s]", $order['state']);
            return ;
        }

        // 判断确认时间
        if (! $order['confirmtime']) {
            $this->error = sprintf("订单确认时间错误！[%s]", $order['confirmtime']);
            return ;
        } 
    }
    /**
     * 订单支付优惠信息
     *
     * @param array $order
     * @return array array(
     *      'countdown' => '剩余秒数',
     *      'tips' => '提示信息',
     *      'discount' => 折扣率
     *      'tipico' => 提示图标
     *  )
     */
    public static function payDiscount($order, $flag = 0) {
        $ret = ['countdown' => 0, 'tips' => '', 'discount' => 0,'tipico' =>''];

        if (isset($order['state']) && ! in_array($order['state'], [1, 4])) {
            return $ret;
        }

        if (isset($order['state']) && $order['ispay']){
            return $ret;
        }
        $gotime =  $order['gotime'];
        $columncategory =  $order['linecategory'];
        $paydeadline = $order['paydeadline'];
        $timestamp = $_SERVER['REQUEST_TIME'];
        $confirmtime = $order['confirmtime'];
        if($flag){
            $tips = date("Ym", $confirmtime) == 201509 ? '5%' : '3%';
            $tipo = date("Ym", $confirmtime) == 201509 ? '8%' : '7%';        
        }  else {
            $tips = '3%';
            $tipo = '3%';
        }
        if($flag){
            // 大社立减规则
            $deadtime = mktime(23, 59, 59, date("m",$order['confirmtime'])+1, 10, date("Y",$order['confirmtime'])) ;
            $endcomfirmtime =mktime(23, 59, 59, date("m",$order['createtime'])+1, 0, date("Y",$order['createtime'])) ; 

            if($timestamp <= $deadtime && $order ['confirmtime'] <= $endcomfirmtime){
                $ret['tipico'] = "&#xe8c8;";
                $ret['countdown'] = $deadtime - $timestamp;
                $ret['tips'] = date("Y-m-d",$deadtime)."之前支付 返利{$tips}（指定期限之前支付有效）";
                $ret['discount'] = 0.03;
            }else{
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过24小时不享受支付立减！';
            }
        } elseif (self::isPromote($order)){
               // 乌鲁木齐5%规则
               $countdown =  $confirmtime > 0 ? ($confirmtime + 86400 - $timestamp) : 0;
            if ($countdown > 0 && $order ['confirmtime'] < $gotime){
                $ret['countdown'] = $countdown;
                $ret['tips'] = "内支付完成，立减 5%（指定期限之前支付有效）";
                $ret['discount'] = "5%";
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过24小时不享受支付立减！';
                $ret['discount'] = "5%";
            }
        }elseif($order['siteid']== 36 && date("Ymd")<20160101){
            // 南京立减规则
            $m = date("m",$gotime);
            $time = strtotime($m==12 ? (date("Y")+1)."0101" : (date("Ym")+1)."01");
            if ($time > $timestamp){
                $countdown = $time - $timestamp;
                $ret['tipico'] = "&#xe8c8;";
                $ret['countdown'] = $time-$timestamp;
                $ret['tips'] = "内支付完成，立减 {$tips}（指定期限之前支付有效）";
                $ret['discount'] = $tips;
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '确认超出当下自然月不享受支付立减！';
                $ret['discount'] = $tips;
            }
        }elseif(self::isGuang($order)){
            // 广州、上海、珠海
            $countdown =  $confirmtime > 0 ? ($confirmtime + 86400 - $timestamp) : 0;
            if ($countdown > 0 && $order ['confirmtime'] < $gotime){
                $ret['countdown'] = $countdown;
                $ret['tips'] = "内支付完成，立减 6%（指定期限之前支付有效）";
                $ret['discount'] = '6%';
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过24小时不享受支付立减！';
                $ret['discount'] = '6%';
            }
        }elseif(self::isNanTong($order)){
            // 南通立减规则
            $countdown =  $confirmtime > 0 ? ($confirmtime + 86400 - $timestamp) : 0;
            if ($countdown > 0 && $order ['confirmtime'] < $gotime){
                $ret['countdown'] = $countdown;
                $ret['tips'] = '内支付完成，立减 4%（指定期限之前支付有效）';
                $ret['discount'] =  '4%';
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过24小时不享受支付立减！';
                $ret['discount'] =  '4%';
            }
        }elseif(self::isAdvancePayEnabled($order)){
            // 境外线预付不享受立减（已停用）
            if($order['payamount'] > 0 && $order['ispay'] == 0){
                if ($timestamp < $order['paydeadline']) {
                    $countdown = $order['paydeadline'] - $timestamp;
                    $ret['countdown'] = $countdown;
                    $ret['tips'] = '内支付完成，立减 3%（指定期限之前支付有效）';
                    $ret['discount'] = 0.03;
                } elseif($order['paydeadline'] == 0){
                    $ret['tipico'] = "&#xe8c8;";
                    $ret['tips'] = '尾款支付完成，立减 3%';
                }else {
                    $ret['tipico'] = "&#xe8c8;";
                    $ret['tips'] = '超过指定日期不享受支付立减！';
                }
            }else{
                    $countdown = $order['confirmtime'] + 172800 - $timestamp;
                    $ret['countdown'] = $countdown;
                    if($countdown > 0){
                        $ret['tips'] = '内支付完成预付，未完成预付，订单自动取消';  
                    }else{
                        $ret['tipico'] = "&#xe8c8;";
                        $ret['tips'] = '已确认48小时内未完成预付，订单已自动取消';  
                    }
            }
        } else {
            // 普通订单立减规则
            if ($order ['confirmtime'] < $gotime && $order['confirmtime']+86400 > $timestamp) {
                $countdown = $order['confirmtime'] + 86400 - $timestamp;
                $ret['countdown'] = $countdown;
                $ret['tips'] = "内支付完成，立减 {$tips}（指定期限之前支付有效）";
                $ret['discount'] = $tips;
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过24小时不享受支付立减！';
                $ret['discount'] = $tips;
            }
        }
        // 上海青年旅行社馨·驰誉下单上海站点订单确认后10天内享受立减
        if($order['buyercompanyid'] == 298463 && $order['siteid'] == 72 && $order['ordersite'] == 1 ){
            if ($order ['confirmtime'] < $gotime && $order['confirmtime']+10*86400 > $timestamp) {
                $ret['tipico'] = "";
                $ret['countdown'] = $order['confirmtime']+10*86400 - $timestamp;
                $ret['tips'] = "内支付完成，立减 {$ret['discount']}（指定期限之前支付有效）";
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过10天不享受支付立减！';
            }
        }
        // 山西站山西康辉国际旅行社 3天内确认享受立减
        if($order['buyercompanyid'] == 300973 && ($order['ordersite'] == 1 || $order['ordersite'] == 2 && $order ['confirmtime']>1444579200) ){
            if ($order ['confirmtime'] < $gotime && $order['confirmtime']+3*86400 > $timestamp) {
                $ret['tipico'] = "";
                $ret['countdown'] = $order['confirmtime']+3*86400 - $timestamp;
                $ret['tips'] = "内支付完成，立减 {$ret['discount']}（指定期限之前支付有效）";
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过3天不享受支付立减！';
            }
        }
        //山西太平洋国旅 7天内确认享受立减
        if($order['buyercompanyid'] == 61545 && ($order['ordersite']  == 1 || $order['ordersite'] == 2)){
            if($order['confirmtime'] < $gotime && $order['confirmtime']+7*86400 > $timestamp){
                $ret['tipico'] = "";
                $ret['countdown'] = $order['confirmtime']+7*86400 - $timestamp;
                $ret['tips'] = "内支付完成，立减 {$ret['discount']}（指定期限之前支付有效）";
            } else {
                $ret['tipico'] = "&#xe8c8;";
                $ret['tips'] = '出团当天及之后确认或确认超过7天不享受支付立减！';
            }
        }
        return $ret;
    }
    // 南通立减4% 
    public static function isNanTong($order){
        
         if(date("Ymd") < 20151216 || date("Ymd") > 20160131){
            return false;
        } 
        if( $order['ordersite'] != 2 && $order['ordersite'] != 1){
             return false;
        }
        // 包含站点
        if ($order['siteid'] != 173 ) {
            return false;
        }
        return true; 
    }
    
    // 乌鲁木齐5%规则
    public static function isPromote($order){
        $timestamp = $_SERVER['REQUEST_TIME'];
        if (date("Ymd", $timestamp) < 20151104 || date("Ymd",$timestamp) > 20160430) {
            return false;
        }
        if( $order['website'] !=2 ){
             return false;
        }
        if ($order['siteid'] !=92 ) {
            return false;
        }
        return true;
    }
    
    // 广州、上海、珠海 立减6%
    public static function isGuang($order){
        //   ['245'广州,'72'上海,'389'珠海]
        if($order['ordersite'] !=2){
            return false;
        }
        if (date("Ymd") < 20151210 ) {
             return false;
        }
        
        if(!in_array($order['siteid'], ['245','72','389'] )){
            return false;
        }
        // 广州
        if($order['siteid'] == 245 && (date("Ymd") > 20160110)){
            return false;
        } 
        // 上海
        if($order['siteid'] == 72 && (date("Ymd") > 20151231)){
            return false;
        }    
        // 珠海
        if($order['siteid'] == 389 &&  (date("Ymd") > 20160120)){
            return false;
        }
        return true;
    }
    
    // 是否支付预付
    public static function isAdvancePayEnabled($order) {
        // 取消预付
        return FALSE;
        if ($order['linecategory'] == 2 && $order['siteid'] == 72 ) { //&& in_array($order['siteid'], [27])
            return TRUE;
        }
        return FALSE;
    }
   
    private function __construct() {

    }
}