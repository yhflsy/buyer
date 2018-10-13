<?php defined('APPPATH') or die('No direct script access.');

if (! defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

ini_set('display_errors', APP_DEBUG); // 错误信息
error_reporting(E_ALL ^ E_NOTICE); // 错误报告的模式 屏蔽警告

define('EXT', '.php'); // 定义扩展名
define('DS', '/'); // 定义分隔符
define('PLUGINS_PATH', dirname(ini_get('extension_dir')) . "/extras/");
define('SMARTY_PATH', PLUGINS_PATH . 'smarty/3.1.11/');
define('MODPATH', PLUGINS_PATH . 'kohana/3.3.1/modules' . DS); // 框架的模块路径
define('SYSPATH', PLUGINS_PATH . 'kohana/3.3.1/system' . DS); // 框架核心程序路径
define('CACHEPATH', DOCROOT . 'cache'. DS); // 缓存文件路径
define('LOGPATH', DOCROOT . 'log' . DS); // 日志记录路径

define ( '_ETU6_ROOT_', DOCROOT);
define ( '_ETU6_SKIN_PATH_', _ETU6_ROOT_ . 'skin/' );
define ( '_ETU6_DATA_COMPLIED_PATH_', _ETU6_ROOT_ . 'cache/complied/' );
define ( '_ETU6_DATA_CACHE_PATH_', _ETU6_ROOT_ . 'cache/' );
define ( '_ETU6_TEMPLATES_CACHE_', 0 );

date_default_timezone_set('Asia/Shanghai'); // 时区
/**
 * Define the start time of the application, used for profiling.
 */
if (!defined('KOHANA_START_TIME')) {
    define('KOHANA_START_TIME', microtime(TRUE));
}

/**
 * Define the memory usage at the start of the application, used for profiling.
 */
if (!defined('KOHANA_START_MEMORY')) {
    define('KOHANA_START_MEMORY', memory_get_usage());
}

// Load the core Kohana class
require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'zh_CN.utf-8');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
I18n::lang('en-us');

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
if (getenv('KOHANA_ENV') !== FALSE)
{
	Kohana::$environment = constant('Kohana::'.strtoupper(getenv('KOHANA_ENV')));
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
$openDebug=APP_DEBUG;
if (Filter::str('debug')==='open'){
    $openDebug=true;
}

Kohana::init(array(
 	'base_url' => '/',
 	'index_file'=> 'index.php',
 	'charset' => 'utf-8',
 	'cache_dir'=> CACHEPATH,
 	'errors' => APP_DEBUG,
 	'profile' => $openDebug,
 	'caching' => !APP_DEBUG,
));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	'auth'       => MODPATH.'auth',       // Basic authentication
	'cache'      => MODPATH.'cache',      // Caching with multiple backends
	'database'   => MODPATH.'database',   // Database access
	'image'      => MODPATH.'image',      // Image manipulation
	'orm'        => MODPATH.'orm',        // Object Relationship Mapping
    'restful' =>  '../plugins/clientrest',
    'passport' =>  '../plugins/passport'
));

Cookie::$salt=  Kohana::$config->load("site.cookie_salt"); 
require APPPATH . 'route.php';

// -- Environment setup --------------------------------------------------------
Common::setupEnv();

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(LOGPATH));

/**
 * Execute the main request.
 * A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
 * If no source is specified, the URI will be automatically detected.
 */
if (!defined('SUPPRESS_REQUEST')) {
    /**
     * Execute the main request using PATH_INFO.
     * If no URI source is specified,
     * the URI will be automatically detected.
     */
    $request = Request::factory();
    set_time_limit(0);

    try {
        // Attempt to execute the response
        $response = $request->execute();
    } catch (Exception $e) {
        if (Kohana::$environment == Kohana::DEVELOPMENT) {
            // Just re-throw the exception
            throw $e;
        }

        // Log the error?
        $attributes = array(
            'action' => '500',
            'message' => $e->getMessage()
        );
        Kohana::$log->add(Log::ERROR, Kohana_Exception::text($e));

        if ($e instanceof Http_Exception) {
            $attributes ['action'] = $e->getCode();
        }

        // redirect to 404 response
        $response->redirect(Route::url('error', $attributes), $attributes ['action']);
    }

    if (Kohana::$environment === Kohana::PRODUCTION){
        $httpcode = $response->status();
        $platform = Common::getPlatformByDomain();
        $indexhost = Kohana::$config->load('platform.host.index');
        if ($httpcode >= 500 ){
            $response->body(View::factory('500', array('errorinfo'=>$response->body())));
        } elseif ($httpcode >= 400) {
            $response->body(View::factory('404', array('errorinfo'=>$response->body())));
        }
    }

    if ($response->send_headers()->body()) {
        // Get the total memory and execution time
        $total = array(
            '{memory_usage}' => number_format((memory_get_peak_usage() - KOHANA_START_MEMORY) / 1024, 2) . 'KB',
            '{execution_time}' => number_format(microtime(TRUE) - KOHANA_START_TIME, 5) . ' seconds'
        );

        // Insert the totals into the response
        $response->body(str_replace(array_keys($total), $total, $response->body()));
    }

    /**
     * Display the request response.
     */
    echo $response->body();
}