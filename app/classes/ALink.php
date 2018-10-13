<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class ALink{
    
    private $_union = false;
    private $_param = [];
    private $_uparam = [];
    private $_iparam = [];
    private $_fparam = [];
    
    static $_instance;
    static function instance(){
        
        if(is_null(self::$_instance)){
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
    public function bulid($key, $val, $default){
        if($val === $default){           
            $this->_uparam[$key] = $val;
        }else{
            $this->_param[$key] = $val;
        }       
    }
    
  
    public function setUnion($flag = true){
        $this->_union = (bool) $flag;
    }
    
    public function ignore(){
        if(func_num_args() > 0){
            $this->_iparam = array_flip(func_get_args());
        }
    }
    
    /**
     *  是否展示缺省
     * @param bool $flag
     */
    static function Union($flag = false){
        if(self::$_instance){
            self::$_instance->setUnion($flag);
        }
    }
    
  
    public function param(){
         return $this->_union ? 
                array_merge(array_diff_key($this->_uparam, $this->_fparam, $this->_iparam), $this->_fparam) : $this->_fparam;
    }
    
    public function query(){
        return '?'.http_build_query($this->param());
    }
    
  
    /**
     * 忽略字段
     */
    static function I(){
        if(self::$_instance){
            call_user_func_array(array(self::$_instance, 'ignore'), func_get_args());
        }        
    }    
    
    static function D($delmix= null){
        if(self::$_instance){
            return self::$_instance->delparam($delmix)->query();
        }
    }
    
    static function A($addmix= null){
        if(self::$_instance){
            return self::$_instance->addparam($addmix)->query();
        }        
    }
    
    static function F($addmix= ''){
        if(self::$_instance){
            return self::$_instance->addparam((array)[(string)$addmix => ''])->query();
        }        
    }
    
    /**
     * 指定字段
     * @param type $mix
     * @return type
     */
    static function U($mix = ''){
        if(self::$_instance){
            return self::$_instance->desparam($mix)->query();
        }   
    }


    public function addparam($mix = null){
        if(!is_array($mix)){
            parse_str($mix, $mix);
        }
        
        $this->_fparam = array_merge(array_diff_key($this->_param, (array)$mix), (array)$mix);
        return $this;
    }
    
    public function delparam($mix = null){
        if(!is_array($mix)){
            $mix = explode(',', $mix); 
        }
        $this->_fparam = array_diff_key($this->_param, array_flip($mix));
        return $this;
    }
    
    public function desparam($mix = null){
        if(!is_array($mix)){
            $mix = explode(',', $mix); 
        }
        
        $this->_fparam = array_intersect_key($this->_param, array_flip($mix));
        return $this;
    }
    
    
}