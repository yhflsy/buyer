<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Wiki
 *
 * @author php5
 */
class Controller_Wiki extends Controller_Base {

    Const JD = false;
    //put your code here
    public function action_index() {

        if ($data = file_get_contents($this->rest->getServer().'/help')) {
            $data = json_decode($data,true);

            $this->view['data'] = $data;
            parent::after();
        } else {
            exit("没有数据");
        }
    }
    
    public function action_method(){
        $method = $_GET['method'];
//        $method = 'xxx';
        $m = str_replace('_', '/', $_GET['m']);
        if($this->request->method() == Request::POST){
            $data = Restful::instance()->request(strtolower($m), strtoupper($method), $_POST, 0, ['header_key' =>  'header_value'])->as_xml();
            ob_clean();
            header("content-Type:text/xml;charset=utf-8");
            echo ($data);
            exit;
        }
        
        $this->view['m'] = strtoupper($m);
        $this->view['method'] = strtoupper($method);
        parent::after();
    }
    
    

    public function action_show() {

        $data['serverhost'] = $this->rest->getServer();
        $data['key'] = str_replace('_', '/', $_GET['key']);
        $data['path'] = Kohana::$config->load('restify.media');
        $data['referer'] = 'http://' . $_SERVER['SERVER_NAME'] . URL::site(Request::detect_uri());
        $data['useragent'] = Request::$user_agent;
        $data['samples'] = Kohana::$config->load('restify.samples');
        $data['request'] = $this->request;
        $this->response->body(View::factory('restify/index', $data));
    }

    public function action_request() {

        $restify = Model::factory('Restify');
        $valid = Validation::factory($this->request->post())->labels($restify->labels());
        foreach ($restify->rules() as $field => $rules) {
            $valid->rules($field, $rules);
        }
        $input = $valid->as_array() + array
            (
            'setting_referer' => URL::site(Request::detect_uri()),
            'setting_useragent' => $restify->get_useragent()
        );
        $data = NULL;
        if ($input['config_data_type'] == 'paired') {
            $data = $this->_combine_input('data', $input);
        } else if ($input['config_data_type'] == 'body' AND in_array($input['method'], array(Restify_Request::HTTP_POST, Restify_Request::HTTP_PUT))) {
            $data = $input['config_data_body'];
        }
        $request = Restify_Request::factory()
                ->set_url($input['url'])
                ->set_method($input['method'])
                ->set_headers($this->_combine_input('header', $input))
                ->set_data($data)
                ->set_useragent($input['setting_useragent'])
                ->set_referer($input['setting_referer']);

        $request->keep_cookies(TRUE);

        $response = $request->response();

        if (!$response->has_error()) {
            $output = array
                (
                'http_code' => $response->get_http_code(),
                'content_type' => $response->get_content_type(),
                'headers' => HTML::chars(trim($response->get_headers())),
                'headers_out' => HTML::chars(trim($response->get_headers_out())),
                'cookies' => $this->_sanitize_cookies($response->get_cookies()),
                'content' => $response->get_content()
            );
        } else {
            $output = array('error' => $response->get_error());
        }

        if (isset($output['error'])) {
            $this->response->status(500);
        }

        $this->response->body(json_encode($output))->headers('content-type', 'application/json');
    }
    
       

    /**
     * Get array
     * 
     * @todo	After urldecode, filter input through htmlspecialchars
     * @access	protected
     * @param	string
     * @param	array
     * @return	array
     */
    protected function & _combine_input($prefix, & $input) {
        $return = array();

        $_key = $prefix . '_key';
        $_value = $prefix . '_value';

        if (isset($input[$_key])) {
            foreach ($input[$_key] as $index => $key) {
                if ($key != '' && $key = urldecode($key)) {
                    $return[$key] = (isset($input[$_value][$index])) ? $this->_darray(urldecode($input[$_value][$index])) : FALSE;
                }
            }
        }

        return $return;
    }
    
    
	/**
	 * Cleanse parsed cookie array
	 * 
	 * @access	protected
	 * @param	array
	 * @return	array
	 */
	protected function & _sanitize_cookies(array $rows)
	{
		foreach ($rows as & $row)
		{
			foreach ($row as $key => & $value)
			{
				$row[$key] = HTML::chars($value);
			}
		}
		
		return $rows;
	}
    
    
    protected function _darray($data){       
        
        if($this->_isjson($data) && self::JD){
            $data = json_decode($data, true);
        } elseif(substr($data, 0, 1) == '[' && substr($data, -1, 1) == ']'){
            $data = trim($data);
            $data =  preg_replace("/\s/","",$data);
            if(preg_match_all("/(\w+)\=\>?(\w+)/", $data, $matches)){            
               $data = array_combine($matches[1], $matches[2]);
            }elseif(preg_match_all("/(\w+)/", $data, $matches)){
               $data = $matches[1];
            }
        }
        
        return $data;
    }

    public function after() {
        
    }
    
    
    private function _isjson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }    

}
