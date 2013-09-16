<?php defined('SYSPATH') OR die('No direct access allowed.');

return array(
	'development' => array(
		// Application SDK settings
		'app_id'      => '{APP ID}',
		'secret'      => '{APP SECRET}',
		'cookie'      => TRUE,
		'domain'      => 'localhost',
		'file_upload' => FALSE,
		'status'      => TRUE,
		'xfbml'       => TRUE,
		'lang'        => 'pl_PL',

		// Application basic settings
		'basic_permissions' => 'user_likes,email',
		'tab_url'           => '//localhost/{APP URI}',
		'canvas_url'        => '{APP CANVAS}',
		'canvas'            => TRUE,
		'fbconnect'         => FALSE,
		'fanpage_id'        => '{FANPAGE ID}',
		'fanpage_name'      => '{FANPAGE NAME}',
		'metatags'          => TRUE,
	),
	'production' => array(
		'app_id'      => '{APP ID}',
		'secret'      => '{APP SECRET}',
		'cookie'      => TRUE,
		'domain'      => 'localhost',
		'file_upload' => FALSE,
		'status'      => TRUE,
		'xfbml'       => TRUE,
		'lang'        => 'pl_PL',

		// Application basic settings
		'basic_permissions' => 'user_likes,email',
		'tab_url'           => '//{APP URL}',
		'canvas_url'        => '{APP CANVAS}',
		'canvas'            => TRUE,
		'fbconnect'         => FALSE,
		'fanpage_id'        => '{FANPAGE ID}',
		'fanpage_name'      => '{FANPAGE NAME}',
		'metatags'          => TRUE,
	)
);