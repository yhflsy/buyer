<?php
require_once SMARTY_PATH . '/Smarty.class.php';

class Template extends Smarty {

    static $instance = null;

    public static function getInstance($themes = '') {
//        if (self::$instance == null) {
//            self::$instance = new Template($themes);
//        }
        
        if (self::$instance[$themes] == null) {
            self::$instance[$themes] = new Template($themes);
        }

        return self::$instance[$themes];
    }

    function __construct($templateid) {
        parent::__construct();
        $this->template_dir = _ETU6_SKIN_PATH_ . $templateid;
        $this->compile_dir = _ETU6_DATA_COMPLIED_PATH_;
//        $this->cache_dir = _ETU6_DATA_CACHE_PATH_;
        $this->caching_type = 'memcache';
        $this->left_delimiter = '<!--{';
        $this->right_delimiter = '}-->';
        if (_ETU6_TEMPLATES_CACHE_) {
            $this->cache(intval(_ETU6_TEMPLATES_CACHE_));
        }
    }

    function cache($cachetime = 0) {
        if (intval($cachetime)) {
            $this->caching = true;
            $this->cache_lifetime = intval($cachetime);
        }
    }

    function cached($filename, $ext = '.html') {
        if ($this->version() > '3') {
            return $this->isCached($filename . $ext);
        } else {
            return $this->is_cached($filename . $ext);
        }
    }

    function set($title, $value) {
        $this->assign($title, $value);
    }

    function show($filename = '', $ext = '.html') {
        global $db, $site, $templateFile;
        $filename = $filename ? $filename : $templateFile;
        if ($db->link) {
            $db->close();
        }

        $filepath = (is_array($this->template_dir) ? $this->template_dir [0] : $this->template_dir) . "$filename$ext";
        if (!file_exists($filepath)) {
            $this->redirect('/404.html');
        }

        unset($GLOBALS ['_ENV'], $GLOBALS ['HTTP_ENV_VARS'], $GLOBALS ['HTTP_SERVER_VARS'], $GLOBALS ['HTTP_POST_VARS'], $GLOBALS ['HTTP_GET_VARS'], $GLOBALS ['HTTP_COOKIE_VARS'], $GLOBALS ['HTTP_POST_FILES'], $GLOBALS ['HTTP_COOKIE_VARS']); // 先清空这几个全局变量
        $this->assign($GLOBALS);

        $this->display($filename . $ext);
    }

    function version() {
        return str_replace('Smarty-', '', self::SMARTY_VERSION);
    }

}

/**
 * Memcache 缓存
 *
 * 通过K-V存储的API来把memcache作为Smarty的输出缓存器。
 *
 * 注意memcache要求key的长度只能是256个字符以内，
 * 所以程序中，key都进行sha1哈希计算后才使用。
 *
 * @package CacheResource-examples
 * @author Rodney Rehm
 */
class Smarty_CacheResource_Memcache extends Smarty_CacheResource_KeyValueStore {

    /**
     * memcache 对象
     *
     * @var Memcache
     */
    protected $memcache = null;

    public function __construct() {
        try {
            $this->memcache = new Memcache ();
            $this->memcache->addServer(Kohana::$config->load('cache.default.servers.host'), Kohana::$config->load('cache.default.servers.port.'));
//			$this->memcache->addServer ( '127.0.0.1', 11211 );
        } catch (Exception $ex) {
            
        }
    }

    /**
     * 从memcache中获取一系列key的值。
     *
     * @param array $keys
     *        	多个key
     * @return array 按key的顺序返回的对应值
     * @return boolean 成功返回true，失败返回false
     */
    protected function read(array $keys) {
        $_keys = $lookup = array();
        foreach ($keys as $k) {
            $_k = sha1($k);
            $_keys [] = $_k;
            $lookup [$_k] = $k;
        }
        $_res = array();
        $res = $this->memcache->get($_keys);
        foreach ($res as $k => $v) {
            $_res [$lookup [$k]] = $v;
        }
        return $_res;
    }

    /**
     * 将一系列的key对应的值存储到memcache中。
     *
     * @param array $keys
     *        	多个kv对应的数据值
     * @param int $expire
     *        	过期时间
     * @return boolean 成功返回true，失败返回false
     */
    protected function write(array $keys, $expire = null) {
        foreach ($keys as $k => $v) {
            $k = sha1($k);
            $this->memcache->set($k, $v, 0, $expire);
        }
        return true;
    }

    /**
     * 从memcache中删除
     *
     * @param array $keys
     *        	待删除的多个key
     * @return boolean 成功返回true，失败返回false
     */
    protected function delete(array $keys) {
        foreach ($keys as $k) {
            $k = sha1($k);
            $this->memcache->delete($k);
        }
        return true;
    }

    /**
     * 清空全部的值
     *
     * @return boolean 成功返回true，失败返回false
     */
    protected function purge() {
        return $this->memcache->flush();
    }

}

class Smarty_Memcached extends Smarty {

    public $Memcached_debug = false;
    public $output;
    public $memcache;
    public $mcHost = "127.0.0.1";
    public $mcPort = 12000;
//	public $mcPort = 11211;
    public $ttl = [
        'short' => 600,
        'medium' => 1800,
        'long' => 7200
    ];

    function __construct($templateid) {
        
        if ($this->mcHost == '127.0.0.1') {
            $this->mcHost = Kohana::$config->load('cache.default.servers.host');
            $this->mcPort = Kohana::$config->load('cache.default.servers.port');
        }
//		global $mc;
        parent::__construct();
//		$this->template_dir = _ETU6_SKIN_PATH_ . $templateid;
//		$this->compile_dir = _ETU6_DATA_COMPLIED_PATH_;
//		$this->cache_dir = _ETU6_DATA_CACHE_PATH_;
//		$this->caching_type = 'memcache';
//		$this->left_delimiter = '<!--{';
//		$this->right_delimiter = '}-->';
//
//		$this->caching = false;
//		$this->memcache = $mc;
//		$this->Memcached_connectMemcache ();
    }

    function cache($cachetime = 0) {
        if (intval($cachetime)) {
            $this->caching = true;
            $this->cache_lifetime = intval($cachetime);
        }
    }

    function cached($filename) {
        $key = str_replace(".", "_", $_SERVER ['HTTP_HOST']) . "_cached_template_" . $filename . "_" . base64_encode($_SERVER ['REQUEST_URI']);
        return $this->Memcached_getCache($key);
    }

    function set($title, $value) {
        $this->assign($title, $value);
    }

    function show($ext = 0) {
        global $db, $templateFile;
        $ext = $ext ? '.shtml' : '.html';
        if ($db->link) {
            $db->close();
        }

        $filetime = time();
        $filepath = (is_array($this->template_dir) ? $this->template_dir [0] : $this->template_dir) . "$templateFile$ext";
        if (!file_exists($filepath)) {
            $this->redirect('/404.html');
        } else {
            $filetime = filemtime($filepath);
        }

        $key = str_replace(".", "_", $_SERVER ['HTTP_HOST']) . "_cached_template_" . $templateFile . "_" . base64_encode($_SERVER ['REQUEST_URI']);
        if ($filetime > $this->memcache->get("{$key}_time")) {
            $this->Memcached_debug = true;
        }
        if (!$this->Memcached_getCache($key)) {
            unset($GLOBALS ['_ENV'], $GLOBALS ['HTTP_ENV_VARS'], $GLOBALS ['HTTP_SERVER_VARS'], $GLOBALS ['HTTP_POST_VARS'], $GLOBALS ['HTTP_GET_VARS'], $GLOBALS ['HTTP_COOKIE_VARS'], $GLOBALS ['HTTP_POST_FILES'], $GLOBALS ['HTTP_COOKIE_VARS']); // 先清空这几个全局变量
            $this->assign($GLOBALS);
            $this->Memcached_fetch($key, $templateFile . $ext, 'long');
        }
        echo $this->output;
    }

    function version() {
        return str_replace('Smarty-', '', $this->_version);
    }

    function Memcached_connectMemcache() {
        if (!$this->memcache) {
            $this->memcache = memcache_pconnect($this->mcHost, $this->mcPort);
            if (!$this->memcache) {
                // if we cannot connect to memcache, we cannot collect cache or
                // check if cache is being generated.
                // the page will then run live, and this is dangerous, so exit
                // with error.
                header($_SERVER ['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                echo "500 - Internal Server Error (#901), please refresh page.";
                exit();
            } else {
                return true;
            }
        }
    }

    function Memcached_getCache($key, $method = 'append') {
        if (!preg_match("/^(?:append)|(?:return)$/", $method)) {
            $method = 'append';
        }

        // if debug, return false to run a live request
        if ($this->Memcached_debug) {
            return false;
        }

        $data = memcache_get($this->memcache, $key);
        if ($data) {
            $data = "<!-- from cache -->" . $data;
            if ($method == 'append') {
                $this->output .= $data;
                return true;
            } else {
                return $data;
            }
        } else {
        	$lock = memcache_get($this->memcache, "lock_" . $key);
            if ($lock) {
                // wait for another process to fill cache
                for ($i = 1; $i <= 10; $i ++) {
                	$data = memcache_get($this->memcache, $key);
                    if ($data) {
                        if ($method == 'append') {
                            $this->output .= $data;
                            return true;
                        } else {
                            return $data;
                        }
                    } else {
                        // wait for 100ms
                        usleep(100000);
                    }
                }

                // quit this effort, but don't run live
                if ($method == 'append') {
                    $this->output .= "Could not load data";
                    return true;
                } else {
                    return "Could not load data";
                }
            } else {
                // create lock, generate cache
                memcache_set($this->memcache, "lock_" . $key, 1, 0, 10);
                return false;
            }
        }
    }

    function Memcached_fetch($key, $tpl, $ttl = 'medium', $method = 'append') {
        if (!preg_match("/^(?:append)|(?:return)$/", $method)) {
            $method = 'append';
        }

        // fetch template
        $content = $this->fetch($tpl);

        // if debugging, clear cache and return
        if ($this->Memcached_debug) {
            memcache_delete($this->memcache, $key, 0);
            memcache_delete($this->memcache, "lock_" . $key, 0);

            if ($method == 'append') {
                $this->output .= $content;
                return true;
            } else {
                return $content;
            }
        }

        if (preg_match("/^(?:short)|(?:medium)|(?:long)$/", $ttl)) {
            $ttl = $this->ttl [$ttl];
        } else {
            $ttl = $this->ttl ['medium'];
        }

        // distribute TTL between ttl/2 and ttl*3/2
        $ttl = round($ttl / 2 + $ttl * mt_rand(0, 1024) / 1024);

        // set cache
        if (!memcache_set($this->memcache, $key, $content, 0, $ttl)) {
            if ($method == 'append') {
                $this->output .= "Could not save data.";
                return false;
            } else {
                return "Could not save data.";
            }
        } else {
            // delete lock
            memcache_delete($this->memcache, "lock_" . $key, 0);

            if ($method == 'append') {
                $this->output .= $content;
                return true;
            } else {
                return $content;
            }
        }
    }

    function Memcached_replaceTag($tag, $content) {
    	$this->output = preg_replace("/\[\*[ ]?" . $tag . "[ ]?\*\]/is", $content, $this->output);
        if ($this->output) {
            return true;
        } else {
            false;
        }
    }

}
