<?php

/*
 * CopyRight  2015  
 *
  -------------------------------------------------------------
 * File:     ExportExcel.php （所在文件名）
 * Type:     Controller
 * Name:     ExportExcel（类名/函数名）
 * Version:  1.0 (系统当前版本号)
 * Date:     2015-6-9
 * Directory: /app/classes/ExportExcel
 * @author:   sx
 * Purpose:   导出卖家excel表格
  -------------------------------------------------------------
 */

class ExportExcel {

    //导出finance客户账单列表中excel
    public static function exportFinC($list, $totallist) {
        set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'No.' => $k + 1,
                'orderid' => $v['orderid'],
                'paystate' => ($v['receivable'] == $v['received']) || $v['ispay'] == 1 ? '已付清' : '未付清',
                'gotime' => date("Y-m-d", $v['gotime']),
                'backtime' => date("Y-m-d", $v['backtime']),
                'title' => $v['linetitle'],
                'buyercompany' => $v['companyname'],
                'buyer' => $v['contactname'] . '  (' . $v['tel'] . ')',
                'price' => $v['receivable'],
                'people' => $v['adult'] . '大' . $v['child'] . '小' . $v['baby'] . '婴',
                'buyername' => $v['realname'],
                'createtime' => date("Y-m-d H:i", $v['createtime']),
                'ispay' => date("Y-m-d H:i", $v['confirmtime']),
                'paytime' => $v['paytime'] > 0 ? date("Y-m-d H:i", $v['paytime']) : '',
                'payamount' => $v['receivable'],
                'received' => $v['received'],
                'way' => $v['ispay'] == 1 || $v['payamount']>0 ? '线上交易' : '线下交易',
                'transcode' => $v['transcode'] ? $v['transcode'] : '无',
                'state' => $v['state'] == 1 ? '未审核' : '已审核',
            );
        }
        $export_data[] = array(
            'No.' => '统计',
            'gotime' => $totallist['ordernum'] . '个订单',
            'buyercompany' => $totallist['adult'] . '大' . $totallist['child'] . '小' . $totallist['baby'] . '婴儿',
            'createtime' => '红包抵扣总额：' . $totallist['hongbaoprice'],
            'ispay' => '代金券抵扣总额：' . $totallist['totalvirtualmoney'],
            'payamount' => '应收：' . $totallist['receivable'],
            'received' => '实收：' . $totallist['received'],
        );
        $title = array(
            '序号',
            '订单编号',
            '支付状态',
            '出团日期',
            '回团时间',
            '产品名称',
            '公司名称',
            '客户信息',
            '金额',
            '人数',
            '下单人',
            '下单时间',
            '确认时间',
            '支付时间',
            '应收',
            '实收',
            '付款方式',
            '交易号',
            '审核',
        );
        $filename = sprintf("客户账单（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出财务平台账单excel
    public static function exportFinP($list, $totallist) {
        set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            if ($v['backtime'] <= time()) {
                $export_data[] = array(
                    'createtime' => date("Y-m", $v['createtime']),
                    'gotime' => date("Y-m-d", $v['gotime']),
                    'backtime' => date("Y-m-d", $v['backtime']),
                    'receivable' => $v['receivable'],
                    'received' => $v['received'],
                    'notreceive' => $v['notreceive'],
                    'clearingtime' => date("Y-m-d", $v['clearingtime']),
                    'title' => $v['title'],
                    'sellmobile' => $v['sellmobile'],
                );
            }
        }
        $export_data[] = array(
            'createtime' => '统计',
            'receivable' => '应收：' . $totallist['receivable'],
            'received' => '实收：' . $totallist['received'],
            'notreceive' => '未收：' . ($totallist['receivable'] - $totallist['received']),
        );
        $title = array(
            '年/月',
            '出团时间',
            '回团时间',
            '订单总额',
            '实收',
            '未收',
            '结算时间',
            '操作人员',
            '电话',
        );
        $filename = sprintf("平台账单（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出财务账单统计excel
    public static function exportFinTotal($list, $totallist) {
        set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'No.' => $k + 1,
                'companyname' => $v['companyname'],
                'total' => $v['ordernum'],
                'people' => $v['adult'] . '大' . $v['child'] . '小' . $v['baby'] . '婴',
                'receivable' => $v['receivable'],
                'received' => $v['received'],
                'notreceive' => $v['receivable'] - $v['received'],
            );
        }
        $export_data[] = array(
            'total' => '统计',
            'ordernum' => '订单总数：' . $totallist['ordernum'],
            'people' => $totallist['adult'] . '大' . $totallist['child'] . '小' . $totallist['baby'] . '婴儿',
            'receivable' => '应收：' . $totallist['receivable'],
            'received' => '实收：' . $totallist['received'],
            'notreceive' => '未收：' . ($totallist['receivable'] - $totallist['received']),
        );
        $title = array(
            '序号',
            '客户名称',
            '订单总数',
            '人数',
            '总金额',
            '实收',
            '未收',
        );
        $filename = sprintf("账单统计（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出财务统计账单详细excel
    public static function exportFinDeatil($list, $totallist) {
        set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'No.' => $k + 1,
                'orderid' => $v['orderid'],
                'paystate' => ($v['receivable'] == $v['received']) || $v['ispay'] == 1 ? '已付清' : '未付清',
                'gotime' => date("Y-m-d", $v['gotime']),
                'backtime' => date("Y-m-d", $v['backtime']),
                'title' => $v['linetitle'],
                'buyercompany' => $v['companyname'],
                'buyer' => $v['contactname'] . '  (' . $v['tel'] . ')',
                'price' => $v['receivable'],
                'people' => $v['adult'] . '大' . $v['child'] . '小' . $v['baby'] . '婴',
                'createtime' => date("Y-m-d H:i", $v['createtime']),
                'ispay' => date("Y-m-d H:i", $v['confirmtime']),
                'paytime' => $v['paytime'] > 0 ? date("Y-m-d H:i", $v['paytime']) : '',
                'payamount' => $v['receivable'],
                'received' => $v['received'],
                'state' => $v['state'] == 1 ? '已审核' : '未审核',
            );
        }
        $export_data[] = array(
            'No.' => '统计',
            'gotime' => $totallist['ordernum'] . '个订单',
            'buyercompany' => $totallist['adult'] . '大' . $totallist['child'] . '小' . $totallist['baby'] . '婴儿',
            'createtime' => '红包抵扣总额：' . $totallist['hongbaoprice'],
            'ispay' => '代金券抵扣总额：' . $totallist['totalvirtualmoney'],
            'payamount' => '应收：' . $totallist['receivable'],
            'received' => '实收：' . $totallist['received'],
            'notreceive' => '未收：' . ($totallist['receivable'] - $totallist['received']),
        );
        $title = array(
            '序号',
            '订单编号',
            '支付状态',
            '出团日期',
            '回团时间',
            '产品名称',
            '公司名称',
            '客户信息',
            '金额',
            '人数',
            '下单时间',
            '确认时间',
            '支付时间',
            '应收',
            '实收',
            '审核',
        );
        $filename = sprintf("账单统计明细（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出线路列表excel
    public static function exportLine($params) {
        set_time_limit(0);
        $export_data = array();
        if (is_array($params['list'])) {
            $stateinfo = array('正常',"停止","客满","截止","删除","过期");
            foreach ($params['list'] as $k => $v) {
                $export_data[] = array(
                    'No.' => $v['lineid'],
                    'state' => $stateinfo[$v['state']],
                    'site' => $v['site'] ? $v['site'] : '--',
                    'gocity' => $v['gocity'] ? $v['gocity'] : '--',
                    'backcity' => $v['backcity'] ? $v['backcity'] : '--',
                    'gotime' => date("Y-m-d", $v['gotime']),
                    'title' => $v['brandtitle'] ? ('[' . $v['brandtitle'] . '] ' . $v['title']) : $v['title'],
                    'category' => Kohana::$config->load('common.linequery.linetype')[$v['category']],
                    'jifen' => $v['integral'] . '/成人  ' . $v['integralchild'] . '/儿童  ' . $v['integralchild'] . '/婴儿',
                    'hongbao' => $v['hongbaoprice'] ? $v['hongbaoprice'] : 0,
                    'istake' => $v['takenum'] ? '是' : '否',
                    'traffic' => '往:' . ($v['gotraffic'] ? $v['gotraffic'] : '--') . '  返:' . ($v['backtraffic'] ? $v['backtraffic'] : '--'),
                    'days' => $v['days'] ? $v['days'] : 0,
                    'people' => '总:' . $v['person'] . '  ' . '余:' . ($v['person'] - $v['personorder']),
                    'price' => '成人:' . $v['adultpricemarket'] . '  ' . '儿童:' . $v['childpricemarket'] . '  ' . '婴儿:' . $v['babypricemarket'],
                    'isstop' => $v['isstop'] ? '否' : '是',
                );
            }
        }
        $title = array(
            '产品编号',
            '状态',
            '站点',
            '出港地',
            '返回地',
            '出发団期',
            '产品名称',
            '线路类型',
            '积分',
            '红包',
            '是否接送',
            '交通',
            '天数',
            '人数',
            '门市价',
            '是否上架'
        );
        $filename = sprintf("线路产品（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出散客订单excel
    public static function exportOrder($list,$sitesinfo) {
        set_time_limit(0);
        $export_data = array();
        $ordersite = array(1 =>'馨·驰誉',2 => '馨·欢途',4 => '美程');
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'index' => $k +1,
                'No.' => $v['orderid'],
                'ordersite' => $ordersite[$v['ordersite']] ? $ordersite[$v['ordersite']] : '--',
                'site' => $sitesinfo[$v['siteid']]['siteName'],
                'gotime' => date("Y-m-d", $v['gotime']),
                'backtime' => date("Y-m-d", $v['backtime']),
                'title' => $v['linetitle'],
                'buyercompany' => $v['buyercompanyname'],
                'sellercompanyname' => $v['sellercompanyname'],
                'buyinfo' => $v['buyer'].' '.$v['mobile'],
                'people' => $v['adult'] . '大' . $v['child'] . '小' . $v['baby'] . '婴',
                'amount' => $v['totalprice'],
                'create' => date("Y-m-d H:i", $v['createtime']),
                'ispay' => $v['ispay'] ? '已支付' : ( $v['paytime'] ?  '已预付' : '未支付') ,
                'paytime' => $v['paytime'] ? date("Y-m-d H:i", $v['paytime']) : '--',
                'receive' => $v['paytime'] ? $v['payamount'] : '--',
                'couponamount' => $v['paytime'] ? $v['couponamount'] : '--',
                'pass' => $v['paytime'] ? $v['totalsubtract'] : '--',
                'shouxu' => $v['paytime'] ? $v['poundage'] : '--',
                'received' =>$v['paytime'] ? $v['finalincome'] : '--',
                'tuikuan' => $v['paytime'] ?  $v['refundedamount'] : '--',
                'sellersubtract' => $v['paytime'] ? $v['suppliersubtract']-$v['supplierrefundsubtract'] : '--',
            );
        }
        $title = array(
            '序号',
            '订单编号',
            '下单平台',
            '站点',
            '出团日期',
            '回团日期',
            '产品名称',
            '组团社',
            '订单来源',
            '预定人信息',
            '人数',
            '订单金额',
            '下单时间',
            '支付状态',
            '支付时间',
            '收到',
            '驰誉卷',
            '立减',
            '手续费',
            '实收',
            '退款',
            '供应商立减',
        );
        $filename = sprintf("散客订单（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出预留订单excel
    public static function exportOrderStay($list) {
        set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'No.' => $v['orderid'],
                'state' => Kohana::$config->load('common.order.reservestate')[$v['state']],
                'beginend' => $v['gocity'] . ' -- ' . $v['destination'],
                'gotime' => date("Y-m-d", $v['gotime']),
                'endtime' => date("Y-m-d", $v['backtime']),
                'title' => $v['linetitle'],
                'buyinfo' => '计调（卖）:' . $v['seller'] . '/ ' . '计调（买）:' . $v['buyer'],
                'amount' => '￥'.$v['totalprice'],
                'people' => $v['adult'] . '大' . $v['child'] . '小' . $v['baby'] . '婴',
                'create' => date("Y-m-d", $v['createtime']),
                'buyerconfirm' => $v['buyerreservetime'] ? date("Y-m-d H:i", $v['buyerreservetime']) : '--',
                'sellerconfirm' => $v['sellerreservetime'] ? date("Y-m-d H:i", $v['sellerreservetime']) : '--',
            );
        }
        $title = array(
            '订单编号',
            '状态',           
            '出发-抵达',
            '出团日期',
            '回团日期',
            '产品名称',
            '预订信息',
            '金额',
            '人数',
            '下单',
            '预留时限',
            '确认时限',
        );
        $filename = sprintf("预留订单（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

    //导出加位订单excel
    public static function exportOrderAdd($list) {
       set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'No.' => $v['orderid'],
                'state' => Kohana::$config->load('common.order.addseatstate')[$v['state']],               
                'beginend' => $v['gocity'] . ' -- ' . $v['destination'],
                'gotime' => date("Y-m-d", $v['gotime']),
                'endtime' => date("Y-m-d", $v['backtime']),
                'title' => $v['linetitle'],
                'buyinfo' => '计调（卖）:' . $v['seller'] . '/ ' . '计调（买）:' . $v['buyer'],
                'amount' => '￥'.$v['totalprice'],
                'people' => $v['adult'] . '大' . $v['child'] . '小' . $v['baby'] . '婴',
                'create' => date("Y-m-d", $v['createtime']),
                //'confirm' => $v['state'] == 1 || $v['state'] == 4 ? date("Y-m-d H:i", $v['confirmtime']) : '--',
            );
        }
        $title = array(
            '订单编号',
            '状态',
            '出发-抵达',
            '出团日期',
            '回团日期',
            '产品名称',
            '加位信息',
            '金额',
            '人数',
            '申请时间',
            //'确认时间',
        );
        $filename = sprintf("加位订单（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }
    
    //导出积分excel
    public static function exportJifen($list) {
        set_time_limit(0);
        $export_data = array();
        foreach ((array)$list as $k => $v) {
            $export_data[] = array(
                'main' => $v['detail'],
                'orderid' => $v['orderid'],
                'lineid' => $v['lineid'],
                'linetitle' => $v['linetitle'],
                'begintime' => date("Y-m-d H:i", $v['createtime']),
                'gotime' => date("Y-m-d H:i", $v['gotime']),
                'createtime' => date("Y-m-d H:i", $v['createtimes']),
                'state' => Kohana::$config->load('common.integraltype')[$v['action']],
                'jifen' => $v['integral'],
            );
        }
        $title = array(
            '操作内容',
            '订单编号',
            '产品编号',
            '线路名称',
            '操作时间',
            '出团日期',
            '下单日期',
            '类型',
            '积分',
        );
        $filename = sprintf("积分（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }
    
    //导出车队订单列表excel
    public static function exportTake($list) {
        set_time_limit(0);
        $export_data = array();
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                $export_data[] = array(
                    'No.' => $k+1,
                    'orderCode' => $v['orderCode'],
                    'orderTime' => $v['createDate'],
                    'departureTime' => $v['useDate']. ' ' .$v['departureTime'],
                    'arrivalTime' => $v['useDate']. ' ' .$v['arrivalTime'],
                    'destination' => $v['destination'],
                    'number' => $v['number'] . '/人',
                    'marketPrice' => $v['marketPrice'],
                    'totalAmount' => $v['totalAmount'],
                    'category' => $v['category'] == 0 ? '城际用车' : '市区单接单送',
                    'contacts' => $v['contacts'],
                    'mobile' => $v['mobile'],
                    'type' => $v['type'] == 0 ? '不限' : '',
                    'status' => $v['status'] == 0 ? '未支付' : ($v['status'] == 1 ? '已支付' : ($v['status'] == 2 ? '支付失败' : '已取消')),
                );
            }
        }
        $title = array(
            '序号',
            '订单编号',
            '订单时间',
            '出发时间',
            '返程时间',
            '目的地',
            '来源/人数',
            '市场价',
            '总金额',
            '用车类型',
            '组团社联系人',
            '组团社联系电话',
            '用车方式',
            '状态'
        );
        $filename = sprintf("车队订单（%s）", date("Y-m-d H:i:s"));
        Common::exportExcel($title, $export_data, iconv('utf-8', 'gbk', $filename));
        die();
    }

}
