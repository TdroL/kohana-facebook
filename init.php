<?php defined('SYSPATH') OR die('No direct access allowed.');

require_once Kohana::find_file('vendors', 'facebook/src/facebook');

Route::set('central', 'central/<controller>(/<id>)', array(
		'id' => '.++',
	))
	->defaults(array(
		'directory'  => 'central',
		'action'     => 'get',
	));

Route::set('facebook-channel.html', 'channel.html')
	->defaults(array(
		'directory'  => 'facebook',
		'controller' => 'channel',
		'action'     => 'index',
	));
