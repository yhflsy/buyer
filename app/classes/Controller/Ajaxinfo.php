<?php

class Controller_Ajaxinfo extends Controller_Base {

    public $message = 'Ok';
    public $data = [];
    public $code = 0;

    public function after() {
        echo json_encode([
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function action_memberinfo() {
        if (! $this->user['memberinfo']['id']) {
            return ;
        }

        $key = sprintf("php:memberIntegral:%d", $this->user['memberinfo']['id']);
        $c = $this->redisCache();
        $data = $c->get($key);
        
        if ($c) {
            if ($data = $c->get($key)) {
                $this->data = $data;
                return ;
            }
        }

        $this->data = [
            'integral' => $this->_canuseIntegral(),
            'coupon' => $this->_unusedCoupon(),
            'hongbao' => $this->_unusedHongbao(),
        ];

        if ($c) {
            $c->set($key, $this->data, 1800);
        }
    }

    public function redisCache(){
        if ($this->redisCache === null){
            $this->redisCache = new RedisCache;
        }

        return $this->redisCache;
    }
    
        //生成右侧json数据
    public function action_ajaxright() {
        if (!$this->user) {
            echo'[]';
            die;
        }

        $info = Cookie::get('info');
        $info = json_decode($info, 1);

        if (!$info) {
            echo '[]';
            die;
        }

        //对接口的调用
        $list = array();
        foreach ($info as $key => $value) {
            $result = $this->request('line', self::GET, array('dateid' => $value['dateid'], 'type' => 'dateDetail'), 'line')->result();
            if (!empty($result['detail'])) {
                $list[$key]['id'] = $value['dateid'];
                $list[$key]['title'] = $result['detail']['title'];
            }
        }

        echo json_encode($list, JSON_UNESCAPED_UNICODE);
        die;
    }
    
    //积分
    protected function _canuseIntegral() {
        $params = array(
            'type' => 2,
            'memberid' => $this->user['memberinfo']['id'],
            'page' => Filter::int('p') ? Filter::int('p') : 1,
            'pagesize' => $this->page->size,
        );
        $result = $this->request('integral', self::GET, $params, 'integral')->result();
        return (int) $result['list']['canuse']?:0;
    }

    //代金券
    protected function _unusedCoupon() {
        $params = array(
            'type' => 'search',
            'memberid' => $this->user['memberinfo']['id'],
            'page' => Filter::int('p') ? Filter::int('p') : 1,
            'pagesize' => $this->page->size,
        );
        $result = $this->request('coupon/index', self::GET, $params)->result();
        return (int) $result['tongji']['unused']?:0;
    }

    //红包
    protected function _unusedHongbao() {
        $params = array(
            'type' => 0,//必填用来指定服务名称
            'state' => 0, //-1全部 0未抵扣 1已抵扣 2未兑现 3已兑现
            'tag' => -1, // -1全部 0现金红包 1抵扣红包
            'companyid' => $this->user['companyinfo']['id'], //买家公司ID    //模拟数据
            'page' => $this->page->current,
            'pagesize' => $this->page->size,
        );
        $result = $this->request('hongbao/buyerfinance', self::GET, $params, 'line')->result();
        return (int) $result['statistics']['pricetotal']?:0;
    }
}
