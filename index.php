<?php
define('APP_DEBUG', true);
define('DOCROOT', __DIR__ . '/'); // 根目录
define('APPPATH', __DIR__ . '/app/'); // 项目目录
true === APP_DEBUG and define('DEFAULT_CURL_TIMEOUT', 10);
require APPPATH . 'bootstrap.php';
