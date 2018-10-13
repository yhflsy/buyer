<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Restful
 *
 * @author php5
 */
class Restful extends RestfulBase {

    //put your code here

    private $output;
    static $instance;
    static $debug = false;
    private $serverhost;
    private $async = false;
    private $realMethod = false;

    static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    
    public function setServer($host){
        $this->serverhost = (string)$host;
    }
    
    public function getServer(){
        return $this->serverhost ;
    }
    
    public function _getServer(){
        return $this->serverhost ;
    }
    
    public function setRealMethod($flag = true){
        $this->realMethod = (bool) $flag;
    }
    
    public function setAsync($flag = false){
        $this->async = $flag;
    }
    
    private function _parseUrl($url){
        $params = parse_url($url);
        if(!isset($params['host']) || !isset($params['scheme'])){
            
            $params = parse_url($this->getServer());

            if( !isset($params['host']) || !$params['host'] ){
                throw new Exception("请为请求配置域, 你可以参数中带入完整的uri，或者在请求前使用setServer方法设置域");
            }
            $params['path'] = $url;
        }
        
        $url = sprintf("%s://%s%s%s",
                in_array((string)$params['scheme'], array('http','https')) ? $params['scheme'] : 'http',
                $params['host'],
                isset($params['port']) ? ":{$params['port']}" : "",
                substr($params['path'] , 0, 1) == '/' ? $params['path'] : '/'.$params['path'] );
        return mb_convert_encoding($url, 'gb2312', 'utf-8');
    }
    
    
    static function S($host){
        if(self::$instance){
            self::$instance->setServer($host);
        }
    }
    
    static function Async($flag = true){
        if(self::$instance){
            self::$instance->setAsync($flag);
        }        
    }

    public function request($url, $method, $data = array(), $headerdata = array()) {
        $url = $this->_parseUrl($url);

        if (! is_array($headerdata)) {
            throw new Exception("参数错误，headerdata必须是数组，request(\$url, \$method, \$data = [], \$headerdata = [])");
        }

        // 服务调用信息
        $requestinfo = compact('url', 'method', 'data', 'headerdata');

        if (Kohana::$profiling === TRUE) {
            $apitoken = substr(md5(json_encode($requestinfo) . microtime()), 0, 8);
            $apibench = Profiler::start('服务调用', sprintf("%s[%s]::/log/api/%s/%s.json", is_array($data) && $data['type']?$url."?type=".$data['type']:$url, $method, date("d"), $apitoken));
        }

        // 重置请求方法
        switch ($method) {
            case 'PUT' :
            case 'DELETE' :
                $headerdata['X-HTTP-Method-Override'] = $method;
                !$this->realMethod && $method = 'POST';
                break;
        }

        if($this->_isjson($data)){
            $headerdata['Content-Type'] = "application/json; charset=utf-8"; 
        }

        $this->output = $this->_request($url, $method, $data, $headerdata, Request::$user_agent, 'http://' . $_SERVER['SERVER_NAME'] . Request::detect_uri(), $this->async);

        if (isset($apibench)) {
            Profiler::stop($apibench);
        }

        $apilog = isset($_COOKIE['_apilog']) && $_COOKIE['_apilog'];
        if ( $apilog || isset($apibench)) {
            if (isset($this->output['content'])) {
                if (substr($this->output['content'], 0, 1) == '{') {
                    $requestinfo += array('response' => (array)json_decode((string) $this->output['content'], true));
                } else {
                    $requestinfo += array('response' => $this->output['content']);
                }
            }

            if ($apilog) {
                if (is_string($requestinfo['response']) && strlen($requestinfo['response']) > 1000) {
                    $requestinfo['response'] = "[unreadable in console]";
                }
                error_log(json_encode($requestinfo, JSON_PRETTY_PRINT));
            }

            self::logging($apitoken, $requestinfo);
        }

        if ($apilog) {
            Kohana::$log->add(Log::DEBUG, $requestinfo, NULL, array(
                "channel" => "service",
                "trace" => Kohana::$environment !== Kohana::PRODUCTION ? array_slice(explode("\n", (new Exception)->getTraceAsString()), 1, -7) : null,
            ));
        }

        return $this->_init();
    }

    public function as_array() {
        return $this->get();
    }

    public function as_xml() {
        return $this->_toXml($this->get());
        
    }

    public function as_jsonstr(){
        return json_encode($this->get());
    } 

    public function get($key = '', $default = null) {
        $data = $this->_decode($this->output['content']);
        if ($key) {
            return isset($data[$key]) ? $this->_decode($data[$key]) : $default;
        }
        return $data;
    }

    public function data() {
        return $this->get('data');
    }

    public function method() {
        return $this->get('method');
    }

    public function code() {
        return $this->get('code');
    }

    public function result() {
        return $this->get('result');
    }

    public function header() {
        return $this->get('header');
    }

    public function message() {
        return $this->get('message');
    }

    private function _init() {
        if (self::$debug) {
            if (self::$debug === true) {
                ob_clean();
                header("Content-Type:application/json;charset=utf-8");
                echo json_encode($this->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                die;
            } else {
                var_dump($this->_decode($this->output['content']));
                die;
            }
        }

        if(isset($this->output['error'])){
            $this->output['content'] = array(
                'code' => 504,
                'message' => (string)$this->output['error']
            );
        }
        
        return $this;
    }

    private function _decode($data) {
        if(is_string($data)){
            if ($this->_isjson($data)) {
                $data = json_decode($data, true);
            } elseif (substr($data, 0, 5) == '<?xml') {
                $data = json_decode(json_encode(simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            }
        }

        return $data;
    }

    private function _isjson($string) {
        if(is_string($string)){
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        }
        return false;

    }

    /**
     * 输出XML
     * @param type $arr
     * @param DOMDocument $dom
     * @param type $item
     * @return type
     */
    public function _toXml($arr, $dom = 0, $item = 0) {
        if (!$dom) {
            $dom = new DOMDocument("1.0", 'UTF-8');
        }
        if (!$item) {
            $item = $dom->createElement("root");
            $dom->appendChild($item);
        }
        foreach ($arr as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemx);

            if (!is_array($val)) {
//                $text = $dom->createTextNode($val);
                $text = $dom->createCDATASection($val);
                $itemx->appendChild($text);
            } else {
                $this->_toXml($val, $dom, $itemx);
            }
        }

        $dom->xmlStandalone = true;
        return $dom->saveXML();
    }

    static function _testconnect($host) {
        if ($host) {
            try {
                $host = parse_url($host, PHP_URL_HOST);
                $resouce = fsockopen($host, 80, $errno, $errstr, 15);
                if (!$resouce) {
                    exit("连接失败 请配置正常的 serverhost");
                }
                fclose($resouce);
            } catch (Exception $e) {
                exit("连接不正常请配置正常的 serverhost");
            }
        } else {
            exit("请到site配置服务host");
        }
    }

    public static function logging($key, $info){
        $redis = Debug::getRedis();
        if($redis) {
            if(!$info) {
                return;
            }
            $key = sprintf("apilog:%s:%s", Kohana::$environment, $key); // Common::.$key.date('Y/m/d H/i/s');
            if(!$redis->set($key, json_encode($info), 90000)) {
               return ; 
            }
            $menu = array(
                'file' => $key,
                'time' => time(),
                'url' => is_array($info['data']) && $info['data']['type'] ? $info['url'] . "?type=" . $info['data']['type']  : $info['url'],
                'method' => $info['method'],
            );
            $redis->lPush(sprintf("apiloglatest:%s", Kohana::$environment), json_encode($menu));
            $redis->lTrim(sprintf("apiloglatest:%s", Kohana::$environment), 0, 500);
            return;
        } else {
            $dir = '/log/api/' . date("d");
            $file = $dir . '/' . $key . '.json';
            @mkdir(DOCROOT . $dir, 0777, true);
            @chmod(DOCROOT . $dir, 0777);
            @file_put_contents(DOCROOT . $file, json_encode($info));
            self::loggingLatest($file, $info);
        }
    }

    private static function loggingLatest($file, $info){
        $logfile = DOCROOT . '/log/api/latest.json';
        if (is_file($logfile)){
            $logs = (array) json_decode(file_get_contents($logfile), true);
        } else {
            $logs = array();
        }

        $logs[] = array(
            'time' => time(),
            'url' => is_array($info['data']) && $info['data']['type'] ? $info['url'] . "?type=" . $info['data']['type']  : $info['url'],
            'file' => $file,
            'method' => $info['method'],
        );

        $logs = array_slice($logs, -500);
        @file_put_contents($logfile, json_encode($logs));
    }
}
