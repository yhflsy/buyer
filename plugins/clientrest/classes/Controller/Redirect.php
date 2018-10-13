<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Controller_Redirect extends Controller_Base {
    
    public function action_index(){
        $url = 'http://'.$_SERVER['HTTP_HOST'];
        $param = array(
            'sign' => Filter::str('sign'),
            'url' => Filter::str('u'),
        );
        if($param['sign'] && $param['url']){         
            if(!$this->user || ($this->user && strtolower(md5($this->user['memberinfo']['id'])) == strtolower($param['sign']))){
                $url = $param['url'];
            }                
        }
        header("Location: {$url}");exit;
    }
}
