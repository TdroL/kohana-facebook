<?php defined('SYSPATH') OR die('No direct access');

class Kohana_Controller_Facebook_Channel extends Controller {

	public function action_index()
	{
		$cache_expire = 60 * 60 * 24 * 365; // 1 year

		$config_group = Kohana::$environment == Kohana::DEVELOPMENT
			? 'development'
			: 'production';
		$this->fb = FB::instance($config_group);

		$this->response->headers('Pragma', 'public');
		$this->response->headers('Cache-Control', 'maxage='.$cache_expire);
		$this->response->headers('Expires', gmdate('D, d M Y H:i:s', time() + $cache_expire).' GMT');

		$this->response->body('<script src="//connect.facebook.net/'.$this->fb->config['lang'].'/all.js"></script>');
	}

}