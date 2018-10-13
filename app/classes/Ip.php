<?php

class Ip {

    static function getAccessArea($ip, &$position) {
        $position = self::getAddress($ip);
        $re = array('/中国|中国人民共和国/', '/新疆|内蒙古|宁夏|广西|西藏/', '/.+盟/', '/河北|山西|内蒙古|辽宁|吉林|黑龙江|江苏|浙江|安徽|福建|江西|山东|河南|湖北|湖南|广东|广西|海南|四川|贵州|云南|西藏|陕西|甘肃|青海|宁夏|新疆|香港|澳门|台湾]/', '/省/');
        $v = preg_replace($re, array('', '', ''), $position);
        return strstr($v, '市', 1);
    }

    static function getProvinceName($ip) {
        $address = self::getAddress($ip);
        return substr($address, 0, strpos($address, '省'));
    }

    static function getCityName($ip = NULL) {
        $ip || $ip = self::GeIpAddress();
        $address = self::getAddress($ip);
        $pos = strpos($address, '省');
        $cityname = $pos > 0 ? substr($address, $pos + 3, 6) : substr($address, 0, 6);
        return $cityname;
    }

    static function getAddress($ip) {
        preg_match('/((\w|-)+\.)+[a-z]{2,4}/i', $ip) ? $ip = gethostbyname($ip) : $ip;
        if (!self::isIp($ip)) {
            return $ip;
        } else {
            $iphelper = new QQWry ();
            $iperr = $iphelper->qqwry($ip);
            $address = '';
            if ($iperr === 0) {
                $address = iconv('gbk', 'utf-8', $iphelper->Country . $iphelper->Local);
            }
            return $address;
        }
    }

    static function getCountry($ip = '') {
        $ip || $ip = self::GeIpAddress();
        preg_match('/((\w|-)+\.)+[a-z]{2,4}/i', $ip) ? $ip = gethostbyname($ip) : $ip;
        if (!self::isIp($ip)) {
            return $ip;
        } else {
            $iphelper = new QQWry ();
            $iperr = $iphelper->qqwry($ip);
            $address = $iphelper->Country;
            return $address;
        }
    }

    static function getSitePosition($ip) {
        global $site;
        $position = self::getCountry($ip);
        $comparePisition = explode('|', _ETU6_SITE_POSITION_ARRAY_);
        if (is_array($comparePisition)) {
            foreach ($comparePisition as $k) {
                $pisitionArray = explode('-', $k);
                if (strpos($position, $pisitionArray[0]) !== false) {
                    return $pisitionArray[1];
                }
            }
        }
        return _ETU6_DEFAULE_SITE_POSITION_;
    }

    static function isIp($ip = '') {
        $ip || $ip = self::GeIpAddress();
        $iparray = explode(".", $ip);
        if (count($iparray) < 4 || count($iparray) > 4)
            return 0;
        foreach ($iparray as $ipaddr) {
            if (!is_numeric($ipaddr)) {
                return false;
            }
            if ($ipaddr < 0 || $ipaddr > 255) {
                return false;
            }
        }
        return true;
    }

    //生成uuid
    static function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid =  substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12);
            return $uuid;
        }
    }

    static function GeIpAddress() {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
        /*
          服务器端IP
          $ch = curl_init('http://20140507.ip138.com/ic.asp');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $a = curl_exec($ch);
          preg_match('/\[(.*)\]/', $a, $ip);
          return $ip [1];
         */
        $ip = '';
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public static function _isCrawler() {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (!empty($agent)) {
            $spiderSite = array(
                "TencentTraveler",
                "Baiduspider+",
                "BaiduGame",
                "Googlebot",
                "msnbot",
                "Sosospider+",
                "Sogou web spider",
                "ia_archiver",
                "Yahoo! Slurp",
                "YoudaoBot",
                "Yahoo Slurp",
                "MSNBot",
                "Java (Often spam bot)",
                "BaiDuSpider",
                "Voila",
                "Yandex bot",
                "BSpider",
                "twiceler",
                "Sogou Spider",
                "Speedy Spider",
                "Google AdSense",
                "Heritrix",
                "Python-urllib",
                "Alexa (IA Archiver)",
                "Ask",
                "Exabot",
                "Custo",
                "OutfoxBot/YodaoBot",
                "yacy",
                "SurveyBot",
                "legs",
                "lwp-trivial",
                "Nutch",
                "StackRambler",
                "The web archive (IA Archiver)",
                "Perl tool",
                "MJ12bot",
                "Netcraft",
                "MSIECrawler",
                "WGet tools",
                "larbin",
                "Fish search",
            );
            foreach ($spiderSite as $val) {
                $str = strtolower($val);
                if (strpos($agent, $str) !== false) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    static function taobaoip($ip) {
        $ch = curl_init('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $info = curl_exec($ch);
        curl_close($ch);
        return $info;
    }

    static function curl_get_html($url = 'http://20140507.ip138.com/ic.asp') {
        // 使用 CURL 模拟访问
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0); //设置http头
        ////curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    //设置http头
        //curl_setopt($ch, CURLOPT_ENCODING, "gzip" );         //设置为客户端支持gzip压缩
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); //设置连接等待时间
        curl_setopt($ch, CURLOPT_URL, $url);
        ob_start(); //开启输出缓存;防止直接数据curl的内容
        curl_exec($ch);

        if (curl_errno($ch)) {
            // curl_error($ch);
            return false;
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //页面访问失败
        if (intval($http_code) <> 200) {
            return false;
        }

        curl_close($ch);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

}

class QQWry {

    var $StartIP = 0;
    var $EndIP = 0;
    var $Country = '';
    var $Local = '';
    var $CountryFlag = 0; // 标识 Country位置
    // 0x01,随后3字节为Country偏移,没有Local
    // 0x02,随后3字节为Country偏移，接着是Local
    // 其他,Country,Local,Local有类似的压缩。可能多重引用。
    var $fp;
    var $FirstStartIp = 0;
    var $LastStartIp = 0;
    var $EndIpOff = 0;

    function getStartIp($RecNo) {
        $offset = $this->FirstStartIp + $RecNo * 7;
        @fseek($this->fp, $offset, SEEK_SET);
        $buf = fread($this->fp, 7);
        $this->EndIpOff = ord($buf [4]) + (ord($buf [5]) * 256) + (ord($buf [6]) * 256 * 256);
        $this->StartIp = ord($buf [0]) + (ord($buf [1]) * 256) + (ord($buf [2]) * 256 * 256) + (ord($buf [3]) * 256 * 256 * 256);
        return $this->StartIp;
    }

    function getEndIp() {
        @fseek($this->fp, $this->EndIpOff, SEEK_SET);
        $buf = fread($this->fp, 5);
        $this->EndIp = ord($buf [0]) + (ord($buf [1]) * 256) + (ord($buf [2]) * 256 * 256) + (ord($buf [3]) * 256 * 256 * 256);
        $this->CountryFlag = ord($buf [4]);
        return $this->EndIp;
    }

    function getCountry() {
        switch ($this->CountryFlag) {
            case 1 :
            case 2 :
                $this->Country = $this->getFlagStr($this->EndIpOff + 4);
                // echo sprintf('EndIpOffset=(%x)',$this->EndIpOff );
                $this->Local = (1 == $this->CountryFlag) ? '' : $this->getFlagStr($this->EndIpOff + 8);
                break;
            default :
                $this->Country = $this->getFlagStr($this->EndIpOff + 4);
                $this->Local = $this->getFlagStr(ftell($this->fp));
        }
    }

    function getFlagStr($offset) {
        $flag = 0;
        while (1) {
            @fseek($this->fp, $offset, SEEK_SET);
            $flag = ord(fgetc($this->fp));
            if ($flag == 1 || $flag == 2) {
                $buf = fread($this->fp, 3);
                if ($flag == 2) {
                    $this->CountryFlag = 2;
                    $this->EndIpOff = $offset - 4;
                }
                $offset = ord($buf [0]) + (ord($buf [1]) * 256) + (ord($buf [2]) * 256 * 256);
            } else {
                break;
            }
        }
        if ($offset < 12)
            return '';
        @fseek($this->fp, $offset, SEEK_SET);

        return $this->getStr();
    }

    function getStr() {
        $str = '';
        while (1) {
            $c = fgetc($this->fp);
            // echo "$cn" ;

            if (ord($c [0]) == 0)
                break;
            $str .= $c;
        }
        // echo "$str n";
        return $str;
    }

    function qqwry($dotip = '') {
        if (!$dotip)
            return;
        if (preg_match("/^(127)/", $dotip)) {
            $this->Country = '本地网络';
            return;
        } elseif (preg_match("/^(192)/", $dotip)) {
            $this->Country = '局域网';
            return;
        }

        $nRet = null;
        $ip = $this->IpToInt($dotip);
        $this->fp = fopen(_ETU6_ROOT_ . '../plugins/ip/QQWry.Dat', "rb");
        if ($this->fp == NULL) {
            $szLocal = "OpenFileError";
            return 1;
        }
        @fseek($this->fp, 0, SEEK_SET);
        $buf = fread($this->fp, 8);
        $this->FirstStartIp = ord($buf [0]) + (ord($buf [1]) * 256) + (ord($buf [2]) * 256 * 256) + (ord($buf [3]) * 256 * 256 * 256);
        $this->LastStartIp = ord($buf [4]) + (ord($buf [5]) * 256) + (ord($buf [6]) * 256 * 256) + (ord($buf [7]) * 256 * 256 * 256);

        $RecordCount = floor(($this->LastStartIp - $this->FirstStartIp) / 7);
        if ($RecordCount <= 1) {
            $this->Country = "FileDataError";
            fclose($this->fp);
            return 2;
        }

        $RangB = 0;
        $RangE = $RecordCount;
        // Match ...
        while ($RangB < $RangE - 1) {
            $RecNo = floor(($RangB + $RangE) / 2);
            $this->getStartIp($RecNo);

            if ($ip == $this->StartIp) {
                $RangB = $RecNo;
                break;
            }
            if ($ip > $this->StartIp)
                $RangB = $RecNo;
            else
                $RangE = $RecNo;
        }
        $this->getStartIp($RangB);
        $this->getEndIp();

        if (($this->StartIp <= $ip) && ($this->EndIp >= $ip)) {
            $nRet = 0;
            $this->getCountry();
            // 这样不太好..............所以..........
            $this->Local = str_replace("（我们一定要解放台湾！！！）", "", $this->Local);
        } else {
            $nRet = 3;
            $this->Country = '未知';
            $this->Local = '';
        }
        fclose($this->fp);
        $this->Country = preg_replace("/(CZ88.NET)|(纯真网络)/", "", $this->Country);
        $this->Local = preg_replace("/(CZ88.NET)|(纯真网络)/", "", $this->Local);
        // ////////////看看 $nRet在上面的值是什么0和3，于是将下面的行注释掉
        return $nRet;

        // return "$this->Country $this->Local";#如此直接返回位置和国家便可以了
    }

    function IpToInt($Ip) {
        $array = explode('.', $Ip);
        $Int = ($array [0] * 256 * 256 * 256) + ($array [1] * 256 * 256) + ($array [2] * 256) + $array [3];
        return $Int;
    }

}
