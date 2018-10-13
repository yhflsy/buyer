<?php
/**
 *  程序参数验证类
 *
 * @since 1.0
 * @access public
 * @author EcaiYing<84808313@qq.com>
 * @copyright (c) 2015, TanJiajun $Id$
 */
final class Filter extends Valid {

    const INT = 0;
    const STRING = 1;
    const TEXT = 2;
    const TEXT_BR = 3;
    const FLOAT = 4;
    const DATE = 5;
    const DATE_TIME = 6;

    /**
     * 检查数据长度
     * @param type $val
     * @param type $min
     * @param type $max
     */
    private static function length(&$val, $min = 0, $max = 0) {
        $result = true;

        if ($min != 0) {
            $result = parent::min_length($val, $min);
        }

        if ($result && $max != 0) {
            $result = parent::max_length($val, $max);
        }

        return $result;
    }

    /**
     * 检查是否为整数
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function int($name, $value = 0, $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::INT);
    }

    /**
     * 检查是否为难
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function float($name, $value = 0, $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::FLOAT);
    }

    /**
     * 检测字符串
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function str($name, $value = '', $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::STRING);
    }

    /**
     * 检测文件块(不处理换行)
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @param type $remote
     * @return type
     */
    static function txt($name, $value = '', $max = NULL, $min = NULL, $remote = FALSE) {
        return self::_getPara($name, $value, $max, $min, self::TEXT, FALSE, $remote);
    }

    /**
     * 带换行符处理文本块
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function txtbr($name, $value = '', $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::TEXT_BR);
    }

    /**
     * 检测日期
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function date($name, $value = '', $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::DATE);
    }

    /**
     * 过滤日期[无大小值与date功能一样]
     * @param type $name
     * @param type $format
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function datetime($name, $format = "Y-m-d", $value = 0, $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::DATE_TIME, FALSE, FALSE, $format);
    }

    /**
     * 过滤整数数组
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function intArr($name, $value = 0, $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::INT, TRUE);
    }

    /**
     * 过滤浮点数组
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function floatArr($name, $value = 0, $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::FLOAT, TRUE);
    }

    /**
     * 过滤字符串数组
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */    
    static function strArr($name, $value = '', $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::STRING, TRUE);
    }

    /**
     * 过滤文本块数组
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */    
    static function txtArr($name, $value = '', $max = NULL, $min = NULL, $remote = FALSE) {
        return self::_getPara($name, $value, $max, $min, self::TEXT, TRUE, $remote);
    }

    /**
     * 过滤文本块数组(直接转换回车符成BR)
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @return type
     */
    static function txtbrArr($name, $value = '', $max = NULL, $min = NULL) {
        return self::_getPara($name, $value, $max, $min, self::TEXT_BR, TRUE);
    }

    /**
     * 验证中文(略)
     * @param type $val
     * @param type $default
     * @param type $min
     * @param type $max
     * @param type $regex
     */
    static function ischinese($val, $default = 0, $min = 0, $max = 0, $regex = '') {
        $result = false;
        $result = filter_var($val, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[\x{4e00}-\x{9fa5}]+$/u')));
        return self::errorstr($result, '汉字校验出错');
    }

    /**
     * 检测是QQ
     * @param type $val
     * @param type $max
     * @return type
     */
    static function isqq($val, $max = 18) {
        $result = false;
        $max = intval($max);
        $max <= 0 && $max >= 18 && $max = 18;

        $result = filter_var($val, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^\d{5,18}$/')));
        return self::errorstr($result, "QQ号码");
    }

    /**
     * 检测手机号码
     * @param type $val
     * @param type $regex
     * @return type
     */
    static function ismobile($val) {
        $result = false;
        
        if (strlen($val) == 11) {
            $result = filter_var($val, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^1\d{10}$/')));
        }
        
        return self::errorstr($result, '手机号校验出错');
    }

    /**
     * 检测邮件地址
     * @param type $val
     * @param type $strict
     * @return type
     */
    static function email($val, $strict = false) {
        $result = false;
        $result = parent::email($val, $strict);
        $result && $result = $val;
        return self::errorstr($result, '邮箱校验出错');
    }

    /**
     * 验证货币类型
     * @param type $val
     * @param type $digits
     * @param type $places
     * @return type
     */
    static function money($val, $places = 2, $digits = NULL) {
        $result = false;        
        $result =  parent::decimal($val, $places, $digits);        
        $result && $result = $val;        
        return self::errorstr($result, "数值格式化出错");
    }

    /**
     * 颜色值校验出错
     * @param type $str
     */
    static function color($val) {
        $result = false;
        $result = parent::color($val);        
        $result && $result = $val;
        return self::errorstr($result, '颜色值校验出错');
    }

    /**
     * 验证字符串是否由数值组成
     * @param type $val
     * @param type $default
     * @param type $min
     * @param type $max
     * @param type $regex
     */
    static function number($val, $default = 0, $min = 0, $max = 10, $regex = '') {
        $result = 0;
        $result = self::length($val, $min, $max);
       
        if ($result) {
            $result = parent::digit($val);
            $result && $result = $val;
        }

        return self::errorstr($result, '数值出错');
    }

    /**
     * 验证URL
     * @param type $val
     * @param type $regex
     */
    static function url($val, $regex = '') {
        if ($regex) {
            return filter_var($val, FILTER_VALIDATE_URL);
        }

        $result = false;        
        $result = parent::url($val);
        $result && $result = $val;
        return self::errorstr($result, 'URL出错');
    }

    /**
     * 统一处理过滤
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @param type $type
     * @param type $remote
     * @param type $format
     * @return type
     */
    private static function _getPara($name, $value = '', $max = NULL, $min = NULL, $type = self::STRING, $array = FALSE, $remote = FALSE, $format = "") {
        if ($array) {
            return self::_getArrayParas($name, $value, $max, $min, $type, $remote, $format);
        } else {
            $val = self::_getParas ( $name, $value, $max, $min, $type, $remote, $format );
            class_exists('ALink') && ALink::$_instance && call_user_func_array(array(ALink::instance(), 'bulid'), [$name, $val, $value]);
            return $val;
        }
    }

    /**
     * 统一处理过滤(数组)
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @param type $type
     * @param type $remote
     * @param type $format
     * @return type
     */
    private static function _getParas($name, $value = '', $max = NULL, $min = NULL, $type = self::STRING, $remote = FALSE, $format = "") {
        if (isset($_POST [$name])) {
            $val = trim($_POST [$name]);
        } elseif (isset($_GET [$name])) {
            $val = trim($_GET [$name]);
        } else {
            return $value;
        }

        switch ($type) {
            case self::INT :
                $val = is_numeric($val) ? intval($val) : $value;
                if ($min !== NULL && $val < $min) {
                    $val = $value;
                } elseif ($max !== NULL && $val > $max) {
                    $val = $value;
                }
                break;

            case self::FLOAT :
                $val = is_numeric($val) ? $val : $value;
                if ($min !== NULL && $val < $min) {
                    $val = $value;
                } elseif ($max !== NULL && $val > $max) {
                    $val = $value;
                }
                break;

            case self::TEXT :
                if ($remote) {
                    $val = @self::saveRemoteImages($val);
                }
                
                if ($val) {
                    $val = self::filterHtml($val);
                    if ($min !== NULL && UTF8::strlen($val) < $min) {
                        $val = $value;
                    } elseif ($max !== NULL && UTF8::strlen($val) > $max) {
                        $val = UTF8::substr($val, 0, $max);
                    }
                }
                break;

            case self::TEXT_BR :
                if ($val) {
                    $val = self::filterHtml(self::unHtml($val));
                    if ($min !== NULL && UTF8::strlen($val) < $min) {
                        $val = $value;
                    } elseif ($max !== NULL && UTF8::strlen($val) > $max) {
                        $val = UTF8::substr($val, 0, $max);
                    }
                }
                break;

            case self::DATE :
                if (!self::isdate($val, $format)) {
                    $val = $value;
                }
                break;

            case self::DATE_TIME :
                if (self::isdate($val, $format)) {
                    $val = self::getTime($val);
                    if ($min !== NULL && $val < $min) {
                        $val = $value;
                    } elseif ($max !== NULL && $val > $max) {
                        $val = $value;
                    }
                } else {
                    $val = $value;
                }
                break;

            default : // string
                $val = strip_tags($val);
                // $val = mysql_real_escape_string($val);
                if (get_magic_quotes_gpc()) {
                    $val = str_replace("\"", "&quot;", $val);
                    $val = str_replace("'", "&#039;", $val);
                } else {
                    $val = self::checkString($val);
                }
                if ($name == 'kw' && !self::isUtf8($val)) {
                    $val = iconv('gbk', 'utf-8', $val);
                }
                
                $val = (!$val && $value) ? $value : $val;
                
                if ($val) {
                    if ($min !== NULL && strlen($val) < $min) {
                        $val = $value;
                    } elseif ($max !== NULL && strlen($val) > $max) {
                        $val = substr($val, 0, $max);
                    }
                }
                break;
        }
        
        return $val;
    }

    /**
     * 统一过滤处理[数组]
     * @param type $name
     * @param type $value
     * @param type $max
     * @param type $min
     * @param type $type
     * @param type $remote
     * @param type $format
     * @return type
     */
    private static function _getArrayParas($name, $value = '', $max = NULL, $min = NULL, $type = self::STRING, $remote = FALSE, $format = "") {
        $i = 0;
        $result = null;

        if (isset($_REQUEST [$name])) {
            if (is_array($_REQUEST [$name])) {
                foreach ($_REQUEST [$name] as $val) {
                    if ($val) {
                        switch ($type) {
                            case self::INT :
                                $val = is_numeric($val) ? intval($val) : $value;
                                if ($min !== NULL && $val < $min) {
                                    $val = $value;
                                } elseif ($max !== NULL && $val > $max) {
                                    $val = $value;
                                }
                                break;

                            case self::FLOAT :
                                $val = is_numeric($val) ? $val : $value;
                                if ($min !== NULL && $val < $min) {
                                    $val = $value;
                                } elseif ($max !== NULL && $val > $max) {
                                    $val = $value;
                                }
                                break;

                            case self::TEXT :
                                if ($remote) {
                                    $val = @self::saveRemoteImages($val);
                                }
                                if ($val) {
                                    
                                    $val = self::filterHtml($val);
                                    
                                    if ($min !== NULL && UTF8::strlen($val) < $min) {
                                        $val = $value;
                                    } elseif ($max != NULL && UTF8::strlen($val) > $max) {
                                        $val = UTF8::substr($val, 0, $max);
                                    }
                                }
                                break;

                            case self::TEXT_BR :
                                if ($val) {
                                    $val = self::filterHtml(self::unHtml($val));
                                    
                                    if ($min !== NULL && UTF8::strlen($val) < $min) {
                                        $val = $value;
                                    } elseif ($max !== NULL && UTF8::strlen($val) > $max) {
                                        $val = UTF8::substr($val, 0, $max);
                                    }
                                }
                                break;

                            case self::DATE :
                                if (!self::isdate($val, $format)) {
                                    $val = $value;
                                }
                                break;

                            case self::DATE_TIME :
                                if (self::isdate($val, $format)) {
                                    $val = self::getTime($val);
                                    if ($min !== NULL && $val < $min) {
                                        $val = $value;
                                    } elseif ($max !== NULL && $val > $max) {
                                        $val = $value;
                                    }
                                } else {
                                    $val = $value;
                                }
                                break;

                            default : // string
                                $val = strip_tags($val);
                                
                                if (get_magic_quotes_gpc()) {
                                        $val = str_replace("\"", "&quot;", $val);
                                        $val = str_replace("\'", "&#039;", $val);
                                } else {
                                    $val = self::checkString($val);

                                }
                                
                                if ($name == 'kw' && !self::isUtf8($val)) {
                                    $val = iconv('gbk', 'utf-8', $val);
                                }
                                
                                $val = (!$val && $value) ? $value : $val;
                                
                                if ($val) {
                                    if ($min !== NULL && UTF8::strlen($val) < $min) {
                                        $val = $value; // 小于最小使用缺少值
                                    } elseif ($max !== NULL && UTF8::strlen($val) > $max) {
                                        $val = UTF8::substr($val, 0, $max);
                                    }
                                }
                                break;
                        }
                        $result [$i] = trim($val);
                    } else {
                        $result [$i] = trim($value);
                    }
                    
                    $i ++;
                }
            } else {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $result[] = trim($v);
                    }
                } else {
                    $result = trim($value);
                }
            }
        }
        
        return $result;
    }

    /**
     * 清除HTML标签
     * @param type $str
     * @return type
     */
    static function filterHtml($str) {
        $farr = array(
            "/<!DOCTYPE([^>]*?)>/is", // 过滤
            "/<(\/?)(html|body|head|link|meta|base|input|span|alert|prompt|p)([^>]*?)>/is", // <script
            "/<(script|i?frame|style|title|form)(.*?)<\/\\1>/is", // 等可能引入恶意内容或恶意改变显示布局的代码,如果不需要插入flash等,还可以加入<object的过滤
            "/(<[^>]*?\s+)on[a-z]+\s*?=(\"|')([^\\2]*)\\2([^>]*}-->)/isU", // 过滤javascript的on事件
            "/\s+/", // 过滤多余的空白
            "/'/"
        );
        
        $tarr = array(
            "",
            "",
            "",
            "\\1\\4",
            " ",
            "&#039;"
        );
        
//        $str = strip_tags($str);
        return preg_replace($farr, $tarr, $str);
    }

    /**
     * 过滤SQL字符
     * @param type $str
     * @return type
     */
    private static function checkString($str) {
        $farr = array(
            "//",
            "/(\'|--|;|exec|insert|update|delete|select|alert|prompt)/isU",
            "/<(script|i?frame|style|title|form)(.*?)<\/\\1>/is"
        );
        $tarr = array(
            "",
            "",
            ""
        );
        
        $str = preg_replace($farr, $tarr, trim($str));
        return $str;
    }

    /**
     * HTML 加码
     * @param type $str
     * @return type
     */
    static function unHtml($str) {
        if (phpversion() > '5.4') {
            $str = htmlspecialchars($str, ENT_COMPAT, 'ISO-8859-1');
        } else {
            $str = htmlspecialchars($str, ENT_COMPAT);
        }

        $str = str_replace ( chr ( 32 ) . chr ( 32 ), "&nbsp;&nbsp;", $str );
        $str = str_replace ( chr ( 9 ) . chr ( 9 ), "&nbsp;&nbsp;", $str );
        $str = str_replace ( chr ( 13 ), "", $str );
        $str = str_replace ( chr ( 10 ), "<br/>", $str );
        $str = str_replace ( chr ( 34 ), " ", $str );
        $str = str_replace ( "&quot;", "\"", $str );
        $str = str_replace ( "&#039;", "'", $str );   
        
        return trim($str);
    }

    /**
     * 复原HTML
     * @param type $str
     * @return type
     */
    static function reHtml($str) {
        $str = trim($str);               
        
        if ($str) {
            return nl2br(html_entity_decode($str));
        }
    }

    /**
     * 检测字符是否为UTF-8
     * @param type $str
     * @return boolean
     */
    static function isutf8($str) {
        return mb_check_encoding($str, 'utf-8');
    }

    /**
     * 检测日期
     * @param type $str
     * @param type $format
     * @return boolean
     */
    private static function isdate($str, $format = "Y-m-d") {
        $format = $format ? $format : "Y-m-d";
        $strArr = explode("-", $str);

        if (empty($strArr)) {
            return false;
        }

        foreach ($strArr as $val) {
            if (strlen($val) < 2) {
                $val = "0" . $val;
            }
            $newArr [] = $val;
        }

        $str = implode("-", $newArr);
        $unixTime = strtotime($str);
        $checkDate = date($format, $unixTime);

        if ($checkDate == $str) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测日期时间
     * @param string $str
     * @param type $format
     * @return type
     */
    private static function getTime($str, $format = "Y-m-d") {
        $time = strtotime($str);

        if ($time == 0 && date("Y-m-d", $str) != '1970-01-01') {
            $time = 1970 * 2 - (date("Y", $str));
            $str = $time . date(str_replace("Y-", "", $format), $str);
            $time = strtotime($str);
            $time -= 2 * $time;
        }

        return $time;
    }
    
    /**
     * 防止xss攻击
     * @param type $string
     * @return boolean
     */
     private static function _cleanXss(&$string){
        if (! is_array ( $string )){
            $string = trim ( $string );
            $string = strip_tags ( $string );
            $string = htmlspecialchars ( $string );
            $string = str_replace ( array ('"', "\\", "'", "..", "../", "./"), '', $string );
            
            return True;
        }
    }
    /**
     * 返回信息
     * @param type $result 
     * @param type $msg
     * @param type $flag
     */
    private static function errorstr($result, $msg = '') {
        if ($result) {
            return $result;
        } else {
            return false;
//            throw new Kohana_Exception(sprintf("%s: 校验出错", $msg));
        }
    }

}
