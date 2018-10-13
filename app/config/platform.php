<?php
defined('SYSPATH') or die('No direct script access.');
return [
    'hosts' => array(
        'dev' => array(
            1 => DOMAIN_CY,
            2 => DOMAIN_HT,
        ),
        'test' => array(
            1 => 'test.cy.etu6.org',
            2 => 'test.ht.etu6.org',
        ),
        'product' => array(
            1 => 'www.tripb2b.com',
            2 => 'www.happytoo.cn',
        )
    ),
    'cssHost' => 'http://' . DOMAIN_STATIC, //'http://static.etu6.org/t',
    'websitevalue' => array('tripb2b' => 1, 'happytoo' => 2),
    'host' => array(//默认三个网站的二级域名不同，每个平台买卖首页三个域名的二级域名需一致
        'index' => array(
            'tripb2b' => DOMAIN_CY,
            'happytoo' => DOMAIN_HT,
        ),
        'buyer' => array(//要求买家域名中需含有buyer，与buyer/webconfig中的路由一致
            'tripb2b' => 'buyer.' . DOMAIN_CY,
            'happytoo' => 'buyer.' . DOMAIN_HT,
        ),
        'seller' => array(
            'tripb2b' => 'seller.' . DOMAIN_CY,
            'happytoo' => 'seller.' . DOMAIN_HT,
        ),
        'pay' => array(
            'tripb2b' => 'pay.' . DOMAIN_CY,
            'happytoo' => 'pay.' . DOMAIN_CY,
        ),
        'wei' => array(
            'tripb2bwap.cy.tb.yake.net'
        ),
    ),
];

