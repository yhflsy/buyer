<?php

class Controller_Groupclub extends Controller_Registeredbase {

    public function before() {
        parent::before();
    }

    //注册 第一步
    public function action_index() {
        if ($this->request->method() == Request::POST) {
            $params = [
                'vCode'=>Filter::str('vCode'),
                'mobile'=>Filter::str('mobile'),
            ];
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/groupclub.distributors.html?one='. base64_encode(json_encode($params)).'&isTravel='.Filter::str('isTravel');
            header("Location: $url");
            die();
        }
        $this->view['webs'] = "http://" . $this->sitedomain['index'][$this->platform];
    }
    
    public function action_distributors() {
        $params =[];
        $isTravel = Filter::str('isTravel');
        Filter::str('one') && $params += json_decode(base64_decode(Filter::str('one')), true);//第一步信息$one = json_decode(base64_decode(Filter::str('one')), true); 
        if ($this->request->method() == Request::POST) {
            $password = Filter::str('userPassword') == Filter::str('userConfirmPassword') ? Filter::str('userPassword') : NULL;
            $params += [
                'companyName' => Filter::str('companyName'),
                'regionId' => Filter::str('regionId'),
                'provinceId' => Filter::str('provinceId'),
                'cityId' => Filter::str('cityId'),
                'areaId' => Filter::str('areaId'),
                'detail' => Filter::str('detail'),
                'tel' => Filter::str('tel'),
                'userName' => Filter::str('userName'),
                'qq' => Filter::str('qq'),
                'password' => $password,
                'gender' => Filter::str('gender'),
                'cardZheng' => Filter::str('cardZheng'),
                'cardFan' => Filter::str('cardFan'),
                'realname' => Filter::str('realname'),
                'webSite' => $this->websitevalue[$this->platform],
                'businessLicensePath' => Filter::str('businessLicensePath'), //营业执照
                'travelPath' => Filter::str('travelPath'), //经营许可证
                'businessLicenseStart' => Filter::str('businessLicenseStart'),
                'businessLicenseEnd' => Filter::str('businessLicenseEnd')!='长期'?Filter::str('businessLicenseEnd'):'',// != '长期'?:1
                'islongtime'=> Filter::str('businessLicenseEnd')=='长期'?1:0,
                'isTravel'=>$isTravel==1 ? 1 :0,
                'isSeller' => 0,
                'terminal'=>0,
            ];          
            $Field = array('userName','realname','mobile','vCode','password','companyName','regionId','provinceId','cityId','areaId','detail');
            if($isTravel == 1){
                $addField = array('businessLicensePath','travelPath','businessLicenseStart');
            }else{
                $addField = array('cardZheng','cardFan');
            }
            $allField= array_merge($Field,$addField);
            //判断参数是否必填
            $errorurl = "http://" . $this->sitedomain['index'][$this->platform].'/groupclub.distributors.html?p='. base64_encode(serialize($params)).'&isTravel='.$isTravel;
            foreach ($allField as $k) {
                if (empty($params[$k])) {
                    $this->showMessage('参数不全或密码不一致！', $errorurl);
                }
            }
            $company_res = $this->jrequest('/api/uc/account', self::ADD, $params,'member')->get(); //添加公司信息
            if ($company_res['code'] == 200) {
                $url = "http://" . $this->sitedomain['index'][$this->platform].'/groupclub.audit.html';
                header("Location: $url");
                die();
            } else {
                $this->showMessage('注册失败，请稍后注册...',$errorurl);
            }
        }
        Filter::str('one') && $this->view['registers'] = $params;
        Filter::str('p') && $this->view['registers'] = unserialize(base64_decode(Filter::str('p')));
        $this->view['isTravel'] = $isTravel;
    }
    
    public function action_audit(){
        
    }


}
