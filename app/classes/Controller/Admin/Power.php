<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * @角色权限
 */

class Controller_Admin_Power extends Controller_Base{
    
    public function before(){
        parent::before();
        $this->themes = 'web.tripb2b.v3.1';
    }
    
    //角色列表
    public function action_index() {
        $this->_check(11404);
        if($this->request->is_ajax()){
            $params = [
                'companyid' => $this->user['companyinfo']['id'],
                'name' => Filter::str('name'),//角色名称
                'curpageno' => Filter::int('p',1),
                'pagesize'=>Filter::int('ps',20),
            ];
            $data = $this->jrequest('/api/role/list', self::ADD, $params)->get();
            $data['result']['pagesize'] = $params['pagesize'];
     //       $this->getCoins($this->user['memberinfo']['id'],134,$this->user['memberinfo']['username']);
            echo json_encode($data);die;
        }
    }

    //添加、编辑角色
    public function action_addRole() {
        $Type = Filter::int('type');//标识作用 有值》1 只展示没有保存操作
        $id = Filter::int('id');//角色ID
        if(!$id){//添加
            $this->_check(11401);
            if($this->request->is_ajax()) {
                $params = $this->_getParams();
                $params['resourceids'] = implode(',', (array)$params['resourceids']);
                $params['privids'] = implode(',', (array)$params['privids']);
                $post = $this->jrequest('/api/role/add', self::ADD, $params)->get();
                echo json_encode($post);die;
            }
        }else{//编辑
            $this->_check(11403);
            if($this->request->is_ajax()) {
                $params = $this->_getParams();
                $params['roleid'] = $id;
                $params['resourceids'] = implode(',', (array)$params['resourceids']);
                $params['privids'] = implode(',', (array)$params['privids']);
                $result = $this->jrequest('/api/role/update', self::UPDATE, $params)->get();
                echo json_encode($result);die;
            }
            //权限详情
            $roleInfo = $this->jrequest('/api/role/detail/'.$id, self::GET)->get();
            if(intval($roleInfo['code']) !== 200){
                $this->showErrMessage($roleInfo['code'], $roleInfo['message']);
            }
            $roleInfo = $roleInfo['result'];
            //根据信息取resourceid、privoid(防止键值为零，取id做键)
            $resourceid = $privoid =[];
            foreach ((array)$roleInfo['resources'] as $value) {
                $resourceid[$value['resourceid']] = $value['resourceid'];
                foreach ((array)$value['privs'] as $val) {
                    $privoid[$val['privid']] = $val['privid'];
                }
            }
        }
        
        //获取所有权限
        $data = $this->jrequest('/api/role/resource/'.$this->user['companyinfo']['id'], self::GET)->get();
        if(intval($data['code']) !== 200){
            $this->showErrMessage($data['code'], $data['message']);
        }
        $this->view = [
            'details' => $data['result'],
            'roleinfo' => $roleInfo,
            'resourceid' => $resourceid,
            'privoid' => $privoid,
            'id'=>$id,
            'type'=>$Type
        ];
    }

    //角色删除
    public function action_ajaxDelRole() {
        $this->_check(11402);
        $id = Filter::int('id');
//        if(empty($id)) {
//            $id[] = Filter::int('id');
//        }
        $data = $this->jrequest('/api/role/delete/'.$id, self::DELETE)->get();
        echo json_encode($data);
        exit();
    }
    
    //获取参数
    private function _getParams() {
        return [
            'companyid' => $this->user['companyinfo']['id'], //公司id,
            'name' => Filter::str('name'),//角色名称
            'memo' => Filter::txt('memo'),//角色备注
            'resourceids' => Filter::intArr('resourceids'), //资源集合,多个以 ","分割,
            'privids' => Filter::intArr('privids'), //权限集合,多个以 ","分割
        ];
    }
}
