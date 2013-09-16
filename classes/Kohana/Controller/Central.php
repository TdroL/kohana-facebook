<?php defined('SYSPATH') OR die('No direct access');

abstract class Kohana_Controller_Central extends Controller_Facebook_AJAX {

	public $id = NULL;

	public function before()
	{
		$this->id = $this->request->param('id');

		$method = $this->request->method();

		if ($override_method = $this->request->headers('X-HTTP-Method-Override'))
		{
			$method = $override_method;
		}
		else if ($method == HTTP_Request::PUT)
		{
			$put = array();
			parse_str($this->request->body(), $put);

			$this->request->post($put);
		}

		$action = strtolower($method);

		if (array_key_exists('id', $this->request->param()))
		{
			if (is_numeric($this->id))
			{
				// action_{method}_id
				$action = $action.'_id';
			}
			else if (strpos($this->id, '/') !== FALSE)
			{
				$parts = explode('/', $this->id, 2);

				if (is_numeric($parts[0]) AND ! is_numeric($parts[1]))
				{
					// action_{method}_id_{relation}
					$action = $action.'_id_'.$parts[1];
					$this->id = $parts[0];
				}
				else if ( ! is_numeric($parts[0]) AND is_numeric($parts[1]))
				{
					// action_{method}_{relation}_id
					$action = $action.'_'.$parts[0].'_id';
					$this->id = $parts[1];
				}
				else
				{
					// action_{method}_{relation-1}_{relation-2}
					$action = $action.'_'.$parts[0].'_'.$parts[1];
					$this->id = NULL;
				}
			}
			else
			{
				// action_{method}_{relation}
				$action = $action.'_'.$this->id;
				$this->id = NULL;
			}
		}

		$action = 'action_'.$this->request->action($action)->action();

		parent::before();

		if ( ! method_exists($this, $action))
		{
			$this->request->query('action', $action);
			$this->request->action('error_not_allowed');
		}
		else if ( ! $this->user->loaded())
		{
			$this->request->action('error_not_logged');
		}

		// Small easter egg :)
		$this->response->headers('X-Powered-By', 'Hamsters. And magic. Lots of magic. t.');
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
			$response = $this->action_error_not_found();
		}
		else
		{
			// Execute the action itself
			try
			{
				$response = $this->{$action}();
			}
			catch (FacebookApiException $e)
			{
				$response = $this->action_error_fb_api($e);
			}
		}

		$response = json_encode($response, (Kohana::$environment != Kohana::PRODUCTION) ? JSON_PRETTY_PRINT : 0);

		$this->response->body($response);

		// Execute the "after action" method
		$this->after();

		// Return the response
		return $this->response;
	}

	public function action_error_not_logged()
	{
		$this->response->status(403);
		return array(
			'message' => 'User not logged in',
			'entity' => 'user',
			'id' => $this->user->id,
		);
	}

	public function action_error_not_found()
	{
		$this->response->status(405);
		return array(
			'message' => 'Resource not found',
		);
	}

	public function action_error_not_allowed()
	{
		$method = $this->request->method();

		if ($override_method = $this->request->headers('X-HTTP-Method-Override'))
		{
			$method = $override_method;
		}

		$this->response->status(405);
		return array(
			'message' => 'Action not allowed',
			'type' => $method,
		) + (Kohana::$environment != Kohana::PRODUCTION ? array('action' => $this->request->query('action')) : array());
	}

	public function action_error_fb_api(FacebookApiException $e = NULL)
	{
		$this->response->status(500);
		return array(
			'message' => 'Invalid FB API request',
		) + (Kohana::$environment != Kohana::PRODUCTION ? array('error' => isset($e) ? $e->getMessage() : NULL) : array());
	}

}