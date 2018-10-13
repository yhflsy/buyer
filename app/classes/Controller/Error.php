<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Controller_Error
 *
 * @author ETu6
 */
class Controller_Error extends Controller_Base {
    
    public function before() {
        parent::before();
    }

    public function action_404() {
//        echo '您访问的页面没有找到! <a href="history.go(-1)">返回</a>';
        $this->view['errorinfo'] = $_REQUEST['errorinfo'];
    }
    
    public function action_500() {
//        echo '网站出错! 请速联系管理员 <a href="mailto:84808313@qq.com">管理员 手机号：18317005375</a>';
        $this->view['errorinfo'] = $_REQUEST['errorinfo'];
    }

}

?>
