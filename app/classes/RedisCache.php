<?php

class RedisCache
{
    public function get($id, $default = NULL) {
        if (! $redis = RedisDB::getRedis()) {
            return false;
        }

        $raw = $redis->get($id);

        if ($raw === false) {
            return $default;
        }

        $json = json_decode($raw, true);

        if ($json === null or json_last_error()) {
            return $raw;
        } else {
            return $json;
        }
    }

    public function set($id, $data, $lifetime = 3600) {
        if (! $redis = RedisDB::getRedis()) {
            return false;
        }

        return $redis->set($id, is_scalar($data) ? $data : json_encode($data), $lifetime);
    }

    public function delete($id) {
        if (! $redis = RedisDB::getRedis()) {
            return false;
        }

        $redis->delete($id);
    }

    public function delete_all() {

    }
}