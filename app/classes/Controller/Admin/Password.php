<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * @密码修改
 */

class Controller_Admin_Password extends Controller_Base{
    
    public function before(){
        parent::before();
        $this->themes = 'web.tripb2b.v3.1';
    }
    
    public function action_index(){
        if($this->request->is_ajax()){
            //获取用户密码
            $memberInfo = $this->jrequest('api/accountManager/queryPersonInfo',self::ADD,['comMemberId'=>$this->user['memberinfo']['id'],'origin'=>1],'member')->get();
            if(!$memberInfo || !isset($memberInfo['result']['password'])){
                $array = array('code'=>1,'msg'=>'无效用户');
                echo json_encode($array);die;
            }
            
            $params = [
                'oldPassword' => Filter::int('oldPassword'),//旧密码
                'newPassword' => Filter::int('newPassword'),//新密码
                'rePassword' => Filter::int('rePassword')//确认密码
            ];
            if(!$params['oldPassword']){
                echo json_encode(array('code'=>100,'msg'=>'原始密码不能为空'));die;
            }
            if(!$params['newPassword']){
                echo json_encode(array('code'=>101,'msg'=>'新密码不能为空'));die;
            }
            if(!$params['rePassword']){
                echo json_encode(array('code'=>102,'msg'=>'确认密码不能为空'));die;
            }
            if($memberInfo['result']['password'] != base64_encode($params['oldPassword'])){
                $array = array('code'=>2,'msg'=>'原始密码错误');
                echo json_encode($array);die;
            }
            if($params['newPassword'] !== $params['rePassword']){
                $array = array('code'=>3,'msg'=>'两次输入的密码不一致');
                echo json_encode($array);die;
            }
            $result = $this->jrequest('api/accountManager/updatePassword', self::ADD, array_merge($params,['comMemberAccountId'=>$memberInfo['result']['comMemberAccountId'],'origin'=>1]))->get();
            if($result['code'] != 200 || $result['result']['type'] != 1){
                echo json_encode(array('code'=>$result['code'],'msg'=>$result['message']));die;
            }else{
                echo json_encode(array('code'=>200,'msg'=>$result['message']));die;
            }
        }
    }
}