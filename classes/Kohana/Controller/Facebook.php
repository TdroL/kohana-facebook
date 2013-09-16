<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Kohana_Controller_Facebook extends Controller_Template
{
	public $template = 'facebook/template';
	public $canvas = NULL;

	public $session;

	/**
	 * Facebook Information about user
	 * @var array
	 */
	public $me = NULL;

	/**
	 * User model
	 * @var Model_User
	 */
	public $user = NULL;

	/**
	 * Facebook Object
	 * @var object
	 */
	public $fb = NULL;

	/**
	 * Facebook's OpenGraph Metatags
	 * @var array
	 * @example $this->metatags['og:locale'] = 'pl_PL'; => <meta property="og:locale" content="pl_PL" />
	 */
	public $metatags = array();

	abstract protected function is_login_required();

	public function before()
	{
		if (Kohana::$profiling === TRUE)
		{
			$name = array($this->request->directory(), $this->request->controller());
			$benchmark = Profiler::start('Controller#before', implode('_', array_filter($name, 'strlen')));
		}

		parent::before();

		/**
		 * Set P3P header
		 */
		$this->response->headers('P3P', 'CP="HONK IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

		/**
		 * Replace "-" with "_" in action name
		 */
		$this->request->action(strtr($this->request->action(), '-', '_'));

		/**
		 * Create canvas view: (directory/)controller/action
		 */
		$this->canvas = View::factory();

		$canvas = $this->request->directory().'/'.$this->request->controller().'/'.$this->request->action();
		$canvas = ltrim(strtolower($canvas), '/');

		if (Kohana::find_file('views', $canvas))
		{
			$this->canvas->set_filename($canvas);
		}

		if ($this->template instanceof View)
		{
			$this->template->bind('canvas', $this->canvas);
		}

		/**
		 * Start session
		 */
		$this->session = Session::instance(NULL, Arr::get($_REQUEST, 'session-id'));

		/**
		 * Save signed_request in session
		 */
		if ( ! isset($_REQUEST['signed_request']))
		{
			$_REQUEST['signed_request'] = Arr::get($_COOKIE, 'signed_request', $this->session->get('signed_request'));
		}

		$this->session->set('signed_request', $_REQUEST['signed_request']);

		/**
		 * Create helper Facebook object
		 */
		$config_group = Kohana::$environment == Kohana::DEVELOPMENT
			? 'development'
			: 'production';
		$this->fb = FB::instance($config_group);

		/**
		 * Check if current controller/action requires logged in user
		 */
		if ($this->is_login_required())
		{
			$this->fb->force_login(NULL, $this->request->uri());
		}

		/**
		 * Retrive user data
		 */
		try
		{
			$fb_data = $this->session->get('facebook', array());

			if (Arr::get($fb_data, 'user_id') == $this->fb->user_id())
			{
				$this->me = Arr::get($fb_data, 'me');
			}

			if ($this->fb->user_id() AND ! $this->me)
			{
				$this->me = $this->fb->me();
			}
		}
		catch (FacebookApiException $e)
		{
			// ignore errors, user probably is not logged in
		}

		/**
		 * Update or save user's data in database
		 */
		$this->user = $this->fb->user($this->me);

		if ($this->fb->user_id())
		{
			$this->session->set('facebook', array(
				'user_id' => $this->fb->user_id(),
				'me' => $this->me,
			));
		}
		else
		{
			$this->session->delete('facebook');
		}

		/**
		 * Bind some usefull data
		 */

		View::bind_global('metatags', $this->metatags);

		View::bind_global('fb_config', $this->fb->config);

		View::set_global('fanpage_url', $this->fb->fanpage_url());

		View::set_global('session', $this->session);

		if ($this->template instanceof View)
		{
			if (Kohana::$environment == Kohana::DEVELOPMENT)
			{
				View::set_global('is_liked', TRUE);
			}
			else
			{
				View::set_global('is_liked', $this->fb->is_page_liked());
			}
		}

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}
	}

	public function execute()
	{
		// Execute the "before action" method
		$this->before();

		// Determine the action to use
		$action = 'action_'.$this->request->action();

		if (Kohana::$profiling === TRUE)
		{
			$name = array($this->request->directory(), $this->request->controller());
			$benchmark = Profiler::start('Controller#execute', implode('_', array_filter($name, 'strlen')).'#action_'.$this->request->action());
		}

		// If the action doesn't exist, it's a 404
		if ( ! method_exists($this, $action))
		{
			throw HTTP_Exception::factory(404,
				'The requested URL :uri was not found on this server.',
				array(':uri' => $this->request->uri())
			)->request($this->request);
		}

		// Execute the action itself
		$this->{$action}();

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

		// Execute the "after action" method
		$this->after();

		// Return the response
		return $this->response;
	}

	public function after()
	{
		if (Kohana::$profiling === TRUE)
		{
			$name = array($this->request->directory(), $this->request->controller());
			$benchmark = Profiler::start('Controller#after', implode('_', array_filter($name, 'strlen')));
		}

		$body = $this->response->body();

		if ( ! empty($body))
		{
			$this->auto_render = FALSE;
		}

		parent::after();

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}
	}
}