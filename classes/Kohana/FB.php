<?php defined('SYSPATH') OR die('No direct access allowed.');

class Kohana_FB {
	/* Facebook driver instance */
	protected static $instances = array();
	protected static $current = NULL;

	/* Facebook SDK object */
	public $graph;

	/* Config data */
	public $config = array();

	/* User model */
	protected $_user;

	/**
	 * Creates instance of the driver
	 * @param string $group  config group
	 * @return FB
	 */
	public static function instance($group = 'default')
	{
		if ( ! isset(FB::$instances[$group]))
		{
			FB::$instances[$group] = new FB(Kohana::$config->load('facebook')->get($group));
		}

		FB::$current = FB::$instances[$group];

		return FB::$instances[$group];
	}

	public static function current()
	{
		return FB::$current;
	}

	public function __construct(array $config = array())
	{
		$this->config = $config;
		$this->graph = new Facebook(array(
			'appId' => $this->config['app_id'],
			'secret' => $this->config['secret'],
			'cookie' => $this->config['cookie'],
			'fileUpload' => $this->config['file_upload'],
		));
	}

	public static function url($route = NULL, array $params = array())
	{
		if ( ! ($route instanceof Route))
		{
			$route = Route::get($route);
		}

		$uri = $route->uri($params);

		if (Kohana::$environment <= Kohana::TESTING)
		{
			return 'https://apps.facebook.com/'.FB::current()->config['canvas_url'].'/'.ltrim($uri, '/');
		}
		else
		{
			return URL::site($uri, Request::current());
		}
	}

	/**
	 * API method alias
	 * @return mixed
	 */
	public function api()
	{
		if (Kohana::$profiling === TRUE)
		{
			$name = func_get_arg(0);

			if (is_array($name))
			{
				if (isset($name['query']))
				{
					$name = $name['query'];
				}
				else if (isset($name['queries']))
				{
					$name = implode('; ', $name['queries']);
				}
			}

			$benchmark = Profiler::start('FB#api', $name);
		}

		$result = call_user_func_array(array($this->graph, 'api'), func_get_args());

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

		return $result;
	}

	/**
	 * API method alias
	 * @return mixed
	 */
	public function query($query)
	{
		return $this->api(array(
			'method' => 'fql.query',
			'query' => $query,
		));
	}

	/**
	 * API method alias
	 * @return mixed
	 */
	public function multiquery($queries)
	{
		return $this->api(array(
			'method' => 'fql.multiquery',
			'queries' => $queries,
		));
	}

	/**
	 * Checks whether user is logged in
	 * @param string $extended_permissions  additional permissions
	 * @return bool
	 */
	public function has_permissions($extended_permissions = NULL)
	{
		$permissions = trim($this->config['basic_permissions'].','.$extended_permissions, ', ');

		$user = $this->graph->getUser();

		if (empty($user))
		{
			return FALSE;
		}

		$acces_danied = FALSE;

		if ( ! empty($permissions))
		{
			$session = Session::instance();

			$cached = $session->get('permissions', array());

			$token = json_encode($this->graph->getSignedRequest());

			if ($cached)
			{
				$cached += array(
					'token' => NULL,
					'permissions' => NULL,
					'acces_danied' => NULL,
				);

				if ($cached['acces_danied'] !== NULL
				    AND $cached['token'] === $token
				    AND $cached['permissions'] == $permissions)
				{
					$acces_danied = $cached['acces_danied'];
				}
				else
				{
					unset($cached);
				}
			}

			if (empty($cached))
			{
				try
				{
					$result = $this->query("SELECT ${permissions} FROM permissions WHERE uid = me()");

					$acces_danied = in_array('0', Arr::get($result, 0, array()), TRUE);

					$session->set('permissions', array(
						'token' => $token,
						'permissions' => $permissions,
						'acces_danied' => $acces_danied,
					));
				}
				catch (FacebookApiException $e)
				{
					$acces_danied = TRUE;
				}
			}
		}

		return ! $acces_danied;
	}

	/**
	 * Requires Facebook login and valid session
	 * @param string $extended_permissions  additional permissions
	 * @param string $uri                   uri used to reditect after successful login
	 */
	public function force_login($extended_permissions = NULL, $uri = NULL)
	{
		if ( ! ($this->has_permissions($extended_permissions)))
		{
			$permissions = trim($this->config['basic_permissions'].','.$extended_permissions, ', ');

			// add tailing slash
			$uri = ltrim($uri, '/');
			$canvas_url = trim($this->config['canvas_url'], '/');
			$redirect_uri = 'https://apps.facebook.com/'.$canvas_url.'/'.$uri;

			// get login url
			$login_url = $this->graph->getLoginUrl(array(
				'display' => 'page',
				'redirect_uri' => $redirect_uri,
				'scope' => $permissions,
			));

			// redirect to login dialog
			$this->redirect_top($login_url);
		}
	}

	/**
	 * Redirects top page of facebook app
	 * @param string $url     target URL
	 * @param bool   $silent  display redirect message
	 */
	public static function redirect_top($url, $silent = TRUE)
	{
		echo View::factory('facebook/redirect', array(
			'url' => $url,
			'silent' => $silent
		));
		exit;
	}

	/**
	 * Returns array of user facebook data
	 * @return array
	 */
	public function me()
	{
		return $this->api('me');
	}

	/**
	 * Returns user facebook id
	 * @return string
	 */
	public function user_id()
	{
		return $this->graph->getUser();
	}

	/**
	 * Returns or sets user model. Setting model refreshes its oauth token.
	 * @param  mixed $me  model to replace current or assoc array with 'id' key
	 * @return Model_User
	 */
	public function user($me = NULL)
	{
		if (func_num_args() == 0)
		{
			// no arguments provided, act as getter
			return $this->_user;
		}

		if ($me instanceof Model_User)
		{
			$this->_user = $me;
		}
		else
		{
			$this->_user = new Model_User;

			if (empty($me) OR ! isset($me['id']))
			{
				// invalid id, return empty model
				return $this->_user;
			}

			$this->_user->where($this->_user->object_name().'.fb_id', '=', $me['id'])->find();

			// update user's data
			$me['fb_id']     = $me['id'];
			$me['user_name'] = Arr::get($me, 'username');
			$me['full_name'] = UTF8::trim(Arr::get($me, 'name'));
			$me['email']     = UTF8::trim(Arr::get($me, 'email'));

			$this->_user->values($me, array(
				'fb_id',
				'user_name',
				'full_name',
				'email',
			));

			// insert or update user
			$this->_user->save();

			if ( ! $this->_user->stat->loaded())
			{
				$stat = new Model_Stat();
				$stat->user = $this->_user;
				$stat->fb_id = $this->_user->fb_id;


				$stat->save();

				// reload current user
				$this->_user->reload();
			}
		}

		return $this->_user;
	}

	/**
	 * Returns list of friends
	 * @param  array $columns  list of requested columns
	 * @param  array $where    assoc array of conditions, eg. [key => value] will be appended to query as "... WHERE 'key' = 'value'"
	 * @return array
	 */
	public function friends(array $columns = array('uid', 'name'), array $where = array())
	{
		// build condition
		$append = '';
		foreach ($where as $key => $value)
		{
			if (Arr::is_array($value))
			{
				// concatenate
				$values = FB::quote($values);

				$append .= " AND '${key}' IN (${values})";
			}
			else
			{
				$value = FB::quote($value);
				$append .= " AND '${key}' = ${value}";
			}
		}

		return $this->query('SELECT '.implode(',', $columns).' FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())'.$append);
	}

	/**
	 * Checks whether user has "liked" current tab page or fan page
	 * @return bool
	 */
	public function is_page_liked()
	{
		if (Arr::path($this->graph->getSignedRequest(), 'page.liked'))
		{
			return TRUE;
		}

		$response = $this->query('SELECT page_id FROM page_fan WHERE uid = me() AND page_id = '.$this->config['fanpage_id']);

		return ! empty($response);
	}

	/**
	 * Generates URL to fan page
	 */
	public function fanpage_url()
	{
		return 'https://www.facebook.com/'.Arr::get($this->config, 'fanpage_name', $this->config['fanpage_id']);
	}

	/**
	 * Quote a value for an FQL query. Copied from Database::quote()
	 */
	public static function quote($value)
	{
		if ($value === NULL)
		{
			return 'NULL';
		}
		elseif ($value === TRUE)
		{
			return "'1'";
		}
		elseif ($value === FALSE)
		{
			return "'0'";
		}
		elseif (is_array($value))
		{
			return '('.implode(', ', array_map('FB::quote', $value)).')';
		}
		elseif (is_int($value))
		{
			return (int) $value;
		}
		elseif (is_float($value))
		{
			// Convert to non-locale aware float to prevent possible commas
			return sprintf('%F', $value);
		}

		$value = mysqli_real_escape_string($value);
		return "'$value'";
	}

}
