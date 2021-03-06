<?php defined('SYSPATH') or die('No direct script access.');
return array
(
	'default' => array(
		'driver'             => 'memcache',
		'default_expire'     => 3600,
		'compression'        => FALSE,              // Use Zlib compression (can cause issues with integers)
		'servers'            => array(
			array(
				'host'             => '192.168.1.251',  // Memcache Server
				'port'             => 20033,        // Memcache port number
				'persistent'       => FALSE,        // Persistent connection
				'weight'           => 1,
				'timeout'          => 1,
				'retry_interval'   => 15,
				'status'           => TRUE,
			),
		),
		'instant_death'      => TRUE,               // Take server offline immediately on first fail (no retry)
	
    ),
	'file'    => array(
		'driver'             => 'file',
		'cache_dir'          => CACHEPATH,
		'default_expire'     => 60,
		'ignore_on_delete'   => array(
			'.gitignore',
			'.git',
			'.svn'
		)
	)
);