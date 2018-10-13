<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * @数据保护
 */

class Controller_Admin_Protect extends Controller_Base{
    
    public function before(){
        parent::before();
        $this->themes = 'web.tripb2b.v3.1';
    }
    
    public function action_index(){
        if($this->request->is_ajax()){
            $params = [
                'companyid' => $this->user['companyinfo']['id'],
                'price' => Filter::int('price'),
                'isstop' => Filter::int('isstop'),
            ];

            $result = $this->request('order/protect', self::ADD, $params, 'order')->get();
            echo json_encode($result);die;
        }
        //设置信息
        $protect = $this->request('order/protect', self::GET, ['companyid' => $this->user['companyinfo']['id']], 'order')->result();
        $this->view['protect'] = $protect;
    }
    
    //取消
    public function action_ajaxProtectstop(){
        if ($this->request->is_ajax()) {
            $params = [
                'companyid' => $this->user['companyinfo']['id'],
                'price' => Filter::int('price'),
                'isstop' => Filter::int('isstop'),
            ];

            $result = $this->request('order/protect', self::ADD, $params, 'order')->get();
            echo json_encode(array("code" => $result['code'], "result" => $result['result']));
            exit;
        }
    }
}