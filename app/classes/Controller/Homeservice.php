<?php

/*
 * 获取共用信息
 * yhf
 * 2016-06-15
 * 订单相关调用服务操作
 */

class Controller_Homeservice {

    //获取类目信息
    static function getCategroys($params, $controller){
        $list = [
            'shortline' => [
                'items' => [],
                'dests' => [],
            ],
            'domestic' => [
                'items' => [],
                'dests' => [],
            ],
            'abroad' => [
                'items' => [],
                'dests' => [],
            ],
        ];

        $request = $controller->request('home/index', Request::GET, $params + ['type' => 'category'], 'line')->get();
        $linedest = $controller->request('home/index', Request::GET, $params + ['type' => 'linedest'], 'line')->get();

        $result = $request['result'] ?: [];
        $linedest =  is_array($linedest['result'])? $linedest['result'] : [];
        if ($result[0]) {
            $list['shortline']['items'] = $result[0];
            $list['shortline']['dests'] = is_array($linedest[0])? $linedest[0] : [];
        }

        if ($result[1]) {
            $list['domestic']['items'] = $result[1];
            $list['domestic']['dests'] = is_array($linedest[1])? $linedest[1] : [];
        }

        if ($result[2]) {
            $list['abroad']['items'] = $result[2];
            $list['abroad']['dests'] = is_array($linedest[2])? $linedest[2] : [];
        }

        return $list;
    }

    //获取类目信息 --旧版
    static function getCategroys1($params, $controller){
        $catparams = $params;
        $catparams['types'] = 'destslist';
        $destParams = $params;
        $destParams['gotimebegin'] = time();

        $list = [
            'shortline' => [
                'items' => [],
                'dests' => [],
            ],
            'domestic' => [
                'items' => [],
                'dests' => [],
            ],
            'abroad' => [
                'items' => [],
                'dests' => [],
            ],
        ];

        //短线
        $catparams['linecategory'] = 0;
        $request = $controller->request('line/categories', Request::GET, $catparams, 'line')->get();
        $result = $request['result'] ?: [];
        if ($result['list']) {
            $list['shortline']['items'] = $result['list'];
            $request = $controller->request('line/dest', Request::GET, $destParams + ['linecategory' => 0], 'line')->get();
            $list['shortline']['dests'] = is_array($request['result']) && is_array($request['result']['list']) ? $request['result']['list'] : [];
        }

        //国内
        $catparams['linecategory'] = 1;
        $request = $controller->request('line/categories', Request::GET, $catparams, 'line')->get();
        $result = $request['result'] ?: [];
        if ($result['list']) {
            $list['domestic']['items'] = $result['list'];
            $request = $controller->request('line/dest', Request::GET, $destParams + ['linecategory' => 1], 'line')->get();
            $list['domestic']['dests'] = is_array($request['result']) && is_array($request['result']['list']) ? $request['result']['list'] : [];
        }

        //出境
        $catparams['linecategory'] = 2;
        $request = $controller->request('line/categories', Request::GET, $catparams, 'line')->get();
        $result = $request['result'] ?: [];
        if ($result['list']) {
            $list['abroad']['items'] = $result['list'];
            $request = $controller->request('line/dest', Request::GET, $destParams + ['linecategory' => 2], 'line')->get();
            $list['abroad']['dests'] = is_array($request['result']) && is_array($request['result']['list']) ? $request['result']['list'] : [];
        }

        return $list;
    }

}
