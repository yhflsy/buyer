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
class RestfulBase {
    //put your code here
    
	/**
	 * restify/create
	 * 
	 * @access	public
	 * @return	void
	 */
           
	protected function _request($url, $method, $data, $header = '', $useragent = '', $referer = '', $async = false){
			$request = Restify_Request::factory()
				->set_url($url)
				->set_method($method)
				->set_headers($header)
				->set_data($data)
				->set_useragent($useragent)
				->set_referer($referer)
                ->set_async($async);
			
			$request->keep_cookies(TRUE);

			$response = $request->response();

			if ( ! $response->has_error())
			{
				$output = array
				(
					'http_code'		=> $response->get_http_code(),
					'content_type'	=> $response->get_content_type(),
					'headers'		=> HTML::chars(trim($response->get_headers())),
					'headers_out'	=> HTML::chars(trim($response->get_headers_out())),
					'cookies'		=> $this->_sanitize_cookies($response->get_cookies()),
					'content'		=> $response->get_content()
				);
			}
			else
			{
				$output = array('error' => $response->get_error());
			}
		return $output;
	}
	
	/**
	 * Cleanse parsed cookie array
	 * 
	 * @access	protected
	 * @param	array
	 * @return	array
	 */
	protected function & _sanitize_cookies(array $rows){
		foreach ($rows as & $row)
		{
			foreach ($row as $key => & $value)
			{
				$row[$key] = HTML::chars($value);
			}
		}
		
		return $rows;
	}    
}
