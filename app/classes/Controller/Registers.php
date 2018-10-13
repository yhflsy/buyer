<?php
class Controller_Registers extends Controller_Registeredbase {

    public function before() {
        parent::before();
    }

    //注册
    public function action_index()
    {
        $platform = $this->websitevalue[$this->platform];
        $choose = Filter::str('choose');
        switch ($choose) {
            case 'zutuanshe':
                $isSeller = 0;
                $register_params = array();
                $requiredField = array();
                break;
            case 'wholesaler':
                $isSeller = 1;
                $register_params = array(
                    'companyBrand'             => Filter::str('companyBrand'),
                    'brandIsStop'              => Filter::int('brandIsStop',0),
                    'lineTypes'                => $_POST['lineTypes'],
                    'logoPath'                 => Filter::str('logoPath'),
                    'dutyInsurancePolicyPath'  => Filter::str('dutyInsurancePolicyPath'),
                    'dutyInsurancePolicyStart' => Filter::str('dutyInsurancePolicyStart'),
                    'dutyInsurancePolicyEnd'   => Filter::str('dutyInsurancePolicyEnd'),
                    'sealPath'                 => Filter::str('sealPath'),
                );
                if(empty($register_params['brandIsStop'])){
                    unset($register_params['brandIsStop']);
                }
                if(is_array($register_params['lineTypes'])){
                    $register_params['lineTypes'] = implode(',',$register_params['lineTypes']);
                }
                $requiredField = array(
                    'logoPath',
                    'dutyInsurancePolicyPath',
                    'dutyInsurancePolicyStart',
                    'dutyInsurancePolicyEnd',
                    'sealPath',
                );
                break;
            case 'dijie':
                $isSeller = 6;
                $register_params = array(
                    'companyBrand'             => Filter::str('companyBrand'),
                    'brandIsStop'              => Filter::int('brandIsStop',0),
                    'lineTypes'                => $_POST['lineTypes'],
                    'logoPath'                 => Filter::str('logoPath'),
                    'dutyInsurancePolicyPath'  => Filter::str('dutyInsurancePolicyPath'),
                    'dutyInsurancePolicyStart' => Filter::str('dutyInsurancePolicyStart'),
                    'dutyInsurancePolicyEnd'   => Filter::str('dutyInsurancePolicyEnd'),
                    'sealPath'                 => Filter::str('sealPath'),
                );
                if(is_array($register_params['lineTypes'])){
                    $register_params['lineTypes'] = implode(',',$register_params['lineTypes']);
                }
                $requiredField = array(
                    'lineTypes',
                    'logoPath',
                    'dutyInsurancePolicyPath',
                    'dutyInsurancePolicyStart',
                    'dutyInsurancePolicyEnd',
                    'sealPath',
                );
                break;
            case 'cedui':
                $isSeller = 5;
                $register_params = array(
                    'logoPath'                 => Filter::str('logoPath'),
                    'transportationPermitPath' => Filter::str('transportationPermitPath'),
                );
                $requiredField = array(
                    'transportationPermitPath'
                );
                break;
            default:
                $webSite = 3;
                $register_params = array();
                $requiredField = array();
                $isSeller = 0;
        }
        $this->view['isSeller'] = $isSeller;
        $this->view['platform'] = $platform;
        $this->view['webs'] = "http://" . $this->sitedomain['index'][$this->platform];
        $terminal = 0;
        if ($this->request->method() == Request::POST) {
            $password = Filter::str('userPassword') == Filter::str('userConfirmPassword') ? Filter::str('userPassword') : NULL;
            $register_params += array(
                'companyName'          => Filter::str('companyName'),
                'regionId'             => Filter::str('regionId'),
                'provinceId'           => Filter::str('provinceId'),
                'cityId'               => Filter::str('cityId'),
                'areaId'               => Filter::str('areaId'),
                'detail'               => Filter::str('detail'),
                'tel'                  => Filter::str('tel'),
                'responsibleFax'       => Filter::str('responsibleFax'),
                'businessLicensePath'  => Filter::str('businessLicensePath'),
                'businessLicenseStart' => Filter::str('businessLicenseStart'),
                'businessLicenseEnd'   => Filter::str('businessLicenseEnd'),
                'travelPath'           => Filter::str('travelPath'),
                'userName'             => Filter::str('userName'),
                'realname'             => Filter::str('realname'),
                'qq'                   => Filter::str('qq'),
                'mobile'               => Filter::str('mobile'),
                'vCode'                => Filter::str('vCode'),
                'password'             => $password,
                'webSite'              => $this->websitevalue[$this->platform],
                'terminal'             => $terminal,
                'isSeller'             => $isSeller,
            );
            $requiredField += array(
                'userName',
                'realname',
                'mobile',
                'vCode',
                'password',
                'companyName',
                'regionId',
                'provinceId',
                'cityId',
                'areaId',
                'detail',
                'tel',
                'businessLicensePath',
                'businessLicenseStart',
                'businessLicenseEnd',
                'travelPath',
            );

            //判断参数是否必填
            foreach ($requiredField as $k) {
                if (empty($register_params[$k])) {
                    $this->showMessage('参数不全或密码不一致！', "http://" . $this->sitedomain['index'][$this->platform].'/registers.html?choose='.$choose.'&p='. base64_encode(serialize($register_params)));
                }
            }

            $company_res = $this->jrequest('/api/uc/account', self::ADD, $register_params,'member')->get(); //添加公司信息
            if ($company_res['code'] == 200) {
                $this->showMessage('注册成功，请等待管理员审核!',"http://" . $this->sitedomain['index'][$this->platform]);
            } else {
                $this->showMessage('注册失败，请稍后注册...',"http://" . $this->sitedomain['index'][$this->platform].'/registers.html?choose='.$choose.'&p='. base64_encode(serialize($register_params)));
            }
        }
        Filter::str('p') && $this->view['registers'] = unserialize(base64_decode(Filter::str('p')));
    }


    public function after() {
        if(!$this->request->is_ajax()){
            parent::after();
        }
    }
}
