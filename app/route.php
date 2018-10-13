<?php

Route::set('common', '(<controller>(.<action>)).html')
        ->defaults(array(
            'controller' => 'index',
            'action' => 'index',
        ));

Route::set('dir', '<directory>(/<controller>(.<action>).html)',
        array(
			'directory' => '[a-zA-Z0-9_/]+'
        ))
        ->defaults(array(
            'controller' => 'index',
            'action' => 'index',
        ));

Route::set('default', '(<controller>(/<action>))')
        ->defaults(array(
            'controller' => 'index',
            'action' => 'index',
        ));
