<?php defined('SYSPATH') OR die('No direct script access.');

class Debug extends Kohana_Debug {

    public static function log($message){
        if (Kohana::$environment == Kohana::PRODUCTION) {
            return;
        }

        Kohana::$log->add(Log::DEBUG, $message);
    }

    public static function info($message){
        if (Kohana::$environment == Kohana::PRODUCTION) {
            return;
        }

        Kohana::$log->add(Log::DEBUG, $message);
    }

    public static function error($message){
        Kohana::$log->add(Log::ERROR, $message);
    }

    public static function exception($exception) {
        Kohana::$log->add(Log::CRITICAL, '', NULL, compact('exception'));
    }

    public static function getRedis($env = null){
        if ($env === null) {
            $env = Kohana::$environment;
        }
        if (isset(self::$_redis[$env])) {
            return self::$_redis[$env];
        }

        self::$_redis[$env] = false;

        if (! class_exists('Redis')) {
            return ;
        }

        switch ($env) {
            case Kohana::PRODUCTION:
                $config = Kohana::$config->load('redis')->product;
                break;
            case Kohana::STAGING:
                $config = Kohana::$config->load('redis')->pre;
                break;
            case Kohana::TESTING:
                $config = Kohana::$config->load('redis')->test;
                break;
            default:
                $config = Kohana::$config->load('redis')->default;
        }

        if (! $config) {
            return false;
        }

        ini_set('default_socket_timeout', -1);

        $redis = new Redis();
        if ($redis->connect($config['redis_host'], $config['redis_port'], $config['connect_timeout'])) {
            if (! empty($config['redis_auth'])) {
                if (! $redis->auth($config['redis_auth'])) {
                    return false;
                }
            }
            self::$_redis[$env] = $redis;
            return $redis;
        }

        return false;
    }

    private static $_redis = array();
}