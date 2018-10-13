<?php

/**
 *
 */
class Controller_Cache extends Controller_Base {

    public function before() {
        parent::before();
    }
    
    public function action_index() {
        if(Filter::str('types')) {
            //清除单个缓存
            $this->_oneCacheclear();
            $this->showMessage('清除缓存成功', 'http://' . $_SERVER['SERVER_NAME']);
        } else if(Filter::str('sign') == 'fjrbbb4xk3w0cacccpvjo0yx866vaa3d') {
            //馨·驰誉平台类目导航栏清除缓存
            $this->_Cacheclear('tripb2b', 'destslist', 0, 1, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->_Cacheclear('tripb2b', 'destslist', 1, 1, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->_Cacheclear('tripb2b', 'destslist', 2, 1, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            //馨·驰誉平台线路列表清除类目缓存
            $this->_Cacheclear('tripb2b', 'dests', 0, 1, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->_Cacheclear('tripb2b', 'dests', 1, 1, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->_Cacheclear('tripb2b', 'dests', 2, 1, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            //馨·欢途平台类目导航栏清除缓存
//            $this->_Cacheclear('happytoo', 'destslist', 0, 2, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
//            $this->_Cacheclear('happytoo', 'destslist', 1, 2, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
//            $this->_Cacheclear('happytoo', 'destslist', 2, 2, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            //馨·欢途平台线路列表清除类目缓存
            $this->_Cacheclear('happytoo', 'dests', 0, 2, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->_Cacheclear('happytoo', 'dests', 1, 2, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->_Cacheclear('happytoo', 'dests', 2, 2, 1); //参数顺序：第一个1,平台，2，线路类型，3，平台标示，4，是否去除缓存
            $this->showMessage('清除缓存成功', 'http://' . $_SERVER['SERVER_NAME']);
        }else{
             $this->showMessage('参数有误', 'http://' . $_SERVER['SERVER_NAME']);
        }
    }

    //去除类目服务缓存
    private function _Cacheclear($platform,$types,$linecategory,$website,$refresh){
        $params = [
            'types'=>$types,
            'linecategory'=>$linecategory,
            'website'=>$website,
            'refresh'=>$refresh
            ];
        $list = $this->request('api/base/sites/platform/'.$platform, self::GET, '','base')->result();
        if($list['code'] != 200){
                $this->showMessageOnly('ERROR-1:'.$list['code'].$list['message']);
            }
        foreach ($list['siteList'] as $key => $value){
            $params['siteid'] = $value['siteId'];
            $result = $this->request('line/categories', self::GET, $params,'line')->result();
            if($result['code'] != 200){
                $this->showMessageOnly('ERROR-2:'.$result['code'].$result['message']);
            }
        }
    }
    private function _oneCacheclear(){
        $params = [
            'types'=>Filter::str('types'),
            'linecategory'=>Filter::str('linecategory'),
            'website'=>Filter::str('website'),
            'refresh'=>1,
            'siteid'=>Filter::str('siteid'),
        ];
        $result = $this->request('line/categories', self::GET, $params,'line')->result();
            if($result['code'] != 200){
                $this->showMessageOnly('ERROR-3:'.$result['code'].$result['message']);
            }
    }

}
