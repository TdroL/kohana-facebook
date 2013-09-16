<?php defined('SYSPATH') OR die('No direct access');

abstract class Kohana_Controller_Facebook_AJAX extends Controller_Facebook {

	public $auto_render = FALSE;

	public function before()
	{
		$this->response->headers('Access-Control-Allow-Origin', '*');
		$this->response->headers('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');

		parent::before();
	}

	public function execute()
	{
		// Execute the "before action" method
		$this->before();

		// Determine the action to use
		$action = 'action_'.$this->request->action();

		// If the action doesn't exist, it's a 404
		if ( ! method_exists($this, $action))
		{
			throw HTTP_Exception::factory(404,
				'The requested URI ":uri" was not found on this server.',
				array(':uri' => $this->request->uri())
			)->request($this->request);
		}

		// Execute the action itself
		$response = $this->{$action}();

		$response = json_encode($response, (Kohana::$environment != Kohana::PRODUCTION AND defined('JSON_PRETTY_PRINT')) ? JSON_PRETTY_PRINT : 0);

		$this->response->body($response);

		// Execute the "after action" method
		$this->after();

		// Return the response
		return $this->response;
	}

	public function after()
	{
		$this->response->headers('Content-Type', File::mime_by_ext('json'));

		parent::after();
	}

}