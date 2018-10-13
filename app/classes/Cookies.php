<?php

define("_COOKIE_TYPE_MEMBER_", 2);
define("_COOKIE_TYPE_REMEMBER_", 4);
define("_COOKIE_TYPE_B2CMEMBER_", 5);
define("_COOKIE_TYPE_REB2CMEMBER_", 6);
define("_COOKIE_TYPE_SITEID_", 8);

class Cookies {

    static $_site_cookie_member_name = "www_happytoo_cn_member_name_cookie";
    static $_site_cookie_remember_name = "www_happytoo_cn_remember_name_cookie";
    static $_site_cookie_b2cmember_name = "www_happytoo_cn_b2cmember_name_cookie";
    static $_site_cookie_reb2cmember_name = "www_happytoo_cn_reb2cmember_name_cookie";
    static $_site_cookie_siteid_name = "wap_happytoo_cn_siteid_name_cookie";
    private $_cookie_name = "";
    private $_cookie_path = "/";
    private $_cookie_domain = "";

    // 初始化
    function __construct($cookieType = _COOKIE_TYPE_MEMBER_) {
        switch ($cookieType) {
            case _COOKIE_TYPE_REMEMBER_ :
                $this->_cookie_name = self::$_site_cookie_remember_name;
                break;
            case _COOKIE_TYPE_B2CMEMBER_ :
                $this->_cookie_name = self::$_site_cookie_b2cmember_name;
                break;
            case _COOKIE_TYPE_REB2CMEMBER_ :
                $this->_COOKIE_TYPE_REB2CMEMBER_ = self::$_site_cookie_reb2cmember_name;
                break;
            case _COOKIE_TYPE_SITEID_ :
                $this->_cookie_name = self::$_site_cookie_siteid_name;
                break;
            default :
                $this->_cookie_name = self::$_site_cookie_member_name;
                break;
        }
        if (!$this->_cookie_domain) {
            $this->_cookie_domain = $_SERVER ['SERVER_NAME'];
        }
    }

    // 设置
    function set($item, $times = null, $path = null, $domain = null) {
        $this->_cookie_path = $path;
        $this->_cookie_domain = $domain;

        setcookie($this->_cookie_name, null, $times, $path, $domain);
        if ($item) {
            if (is_array($item)) {
                $tempItem = null;
                foreach ($item as $key => $val) {
                    $tempItem [$key] = $val;
                }
            } else {
                $tempItem = $item;
            }
            $strCookie = base64_encode(serialize($tempItem));
            setcookie($this->_cookie_name, $strCookie, $times, $path, $domain);
        }
        $this->get();
    }

    // 删除
    function drop($deleSession = 1) {
        if (isset($_COOKIE [$this->_cookie_name])) {
            if ($deleSession) {
                $item = unserialize(base64_decode($_COOKIE [$this->_cookie_name]));
                if (is_array($item)) {
                    foreach ($item as $key) {
                        unset($_SESSION [$this->_cookie_name . "_" . $key]);
                    }
                } else {
                    unset($_SESSION [$this->_cookie_name]);
                }
            }
            $times = time() - 3600;
            $this->set(null, $times, $this->_cookie_path, $this->_cookie_domain);
        }
    }

    // 读取
    function get() {
        $item = null;
        if (isset($_COOKIE [$this->_cookie_name])) {
            $item = unserialize(base64_decode($_COOKIE [$this->_cookie_name]));
            if (is_array($item)) {
                foreach ($item as $key => $val) {
                    $_SESSION [$this->_cookie_name . "_" . $key] = $val;
                }
            } else {
                $_SESSION [$this->_cookie_name] = $item;
            }
        }
        return $item;
    }

}
