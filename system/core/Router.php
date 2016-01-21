<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Router Class
 *
 * Parses URIs and determines routing
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/general/routing.html
 */
class CI_Router {

	/**
	 * CI_Config class object
	 *
	 * @var	object
	 */
	public $config;

	/**
	 * List of routes
	 *
	 * @var	array
	 */
	public $routes =	array();

	/**
	 * Current class name
	 *
	 * @var	string
	 */
	public $class =		'';

	/**
	 * Current method name
	 *
	 * @var	string
	 */
	public $method =	'index';

	/**
	 * Sub-directory that contains the requested controller class
	 *
	 * @var	string
	 */
	public $directory;

	/**
	 * Default controller (and method if specific)
	 *
	 * @var	string
	 */
	public $default_controller;

	/**
	 * Translate URI dashes
	 *
	 * Determines whether dashes in controller & method segments
	 * should be automatically replaced by underscores.
	 *
	 * @var	bool
	 */
	public $translate_uri_dashes = FALSE;

	/**
	 * Enable query strings flag
	 *
	 * Determines whether to use GET parameters or segment URIs
	 *
	 * @var	bool
	 */
	public $enable_query_strings = FALSE;

	// --------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Runs the route mapping function.
	 *
	 * @return	void
	 */
	public function __construct($routing = NULL)
	{
		$this->config =& load_class('Config', 'core');
		$this->uri =& load_class('URI', 'core');

		$this->enable_query_strings = ( ! is_cli() && $this->config->item('enable_query_strings') === TRUE);

		// If a directory override is configured, it has to be set before any dynamic routing logic
		is_array($routing) && isset($routing['directory']) && $this->set_directory($routing['directory']);
		$this->_set_routing();

		// Set any routing overrides that may exist in the main index file
		if (is_array($routing))
		{
			empty($routing['controller']) OR $this->set_class($routing['controller']);
			empty($routing['function'])   OR $this->set_method($routing['function']);
		}

		log_message('info', 'Router Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Set route mapping
	 *
	 * Determines what should be served based on the URI request,
	 * as well as any "routes" that have been set in the routing config file.
	 *
	 * @return	void
	 */
	protected function _set_routing()
	{
		// Load the routes.php file. It would be great if we could
		// skip this for enable_query_strings = TRUE, but then
		// default_controller would be empty ...
		
		if (file_exists(APPPATH.'config/routes.php'))
		{
			include(APPPATH.'config/routes.php');
		}

		if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/routes.php'))
		{
			include(APPPATH.'config/'.ENVIRONMENT.'/routes.php');
		}

		// Validate & get reserved routes
		if (isset($route) && is_array($route))
		{
			isset($route['default_controller']) && $this->default_controller = $route['default_controller'];
			isset($route['translate_uri_dashes']) && $this->translate_uri_dashes = $route['translate_uri_dashes'];
			unset($route['default_controller'], $route['translate_uri_dashes']);
			$this->routes = $route;
		}

		// Are query strings enabled in the config file? Normally CI doesn't utilize query strings
		// since URI segments are more search-engine friendly, but they can optionally be used.
		// If this feature is enabled, we will gather the directory/class/method a little differently
		if ($this->enable_query_strings)
		{
			// If the directory is set at this time, it means an override exists, so skip the checks
			if ( ! isset($this->directory))
			{
				$_d = $this->config->item('directory_trigger');
				$_d = isset($_GET[$_d]) ? trim($_GET[$_d], " \t\n\r\0\x0B/") : '';

				if ($_d !== '')
				{
					$this->uri->filter_uri($_d);
					$this->set_directory($_d);
				}
			}

			$_c = trim($this->config->item('controller_trigger'));
			if ( ! empty($_GET[$_c]))
			{
				$this->uri->filter_uri($_GET[$_c]);
				$this->set_class($_GET[$_c]);

				$_f = trim($this->config->item('function_trigger'));
				if ( ! empty($_GET[$_f]))
				{
					$this->uri->filter_uri($_GET[$_f]);
					$this->set_method($_GET[$_f]);
				}

				$this->uri->rsegments = array(
					1 => $this->class,
					2 => $this->method
				);
			}
			else
			{
				$this->_set_default_controller();
			}

			// Routing rules don't apply to query strings and we don't need to detect
			// directories, so we're done here
			return;
		}

		// Is there anything to parse?
		if ($this->uri->uri_string !== '')
		{
			$this->_parse_routes();
		}
		else
		{
			$this->_set_default_controller();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set request route
	 *
	 * Takes an array of URI segments as input and sets the class/method
	 * to be called.
	 *
	 * @used-by	CI_Router::_parse_routes()
	 * @param	array	$segments	URI segments
	 * @return	void
	 */
	protected function _set_request($segments = array())
	{
		/**
		 * 下面来解剖一下这个让人纠结的函数。。第一次看的时候差点被它们这几个函数搞晕。
		 * 看，这里有调用Router::_validate_request();而Router::_validate_request()的作用是检测寻找出一个
		 * 正确存在的路由，并确定它，确定后的值分别放到Rouer::$class这些属性里面。所以使到这个_set_request()也有
		 * 这种确定路由的功能。
		 *
		 * 注：
		 * $segments=$this->_validate_request($segments); 等式右边，括号里面的这个$segments，也就是调用
		 * _set_request()时传入来的这个参数，它有这样的特点：
		 * 1）如果这时_set_request()是在Router::_set_default_controller()中调用的话，那个这个$segments是永远不会为
		 *  空数组，嗯，绝对不会。
		 *
		 *
		 * 而左边这个$segments的值，经过下面这行代码后，要么为空数组array(),要么为确定路由后的段数组。
		 * 为空数组的原因是，$this->_validate_request();里面没有找到当前目录的默认控制器。此时，右边的
		 * $segments要么为空，要么只指定了目录但默认控制器不存在。
		 */
		$segments = $this->_validate_request($segments);
		// If we don't have any segments left - try the default controller;
		// WARNING: Directories get shifted out of the segments array!
		if (empty($segments))
		{
			//所以如果上面返回了空数组，就会进到这里。
			//这里居然又调回了_set_default_controller()! 坑爹吧！
			$this->_set_default_controller();
			/**
			 * 我曾经想过，下面这里会不会死循环：
			 * 假如，我在配置文件里面的默认控制器设为welcome，然后controllers/下没有welcome.php,但controllers/下有
			 * welcome/有这个目录（里面没东西），然后通过http://localhost/CI/来访问默认控制器，那会怎样呢？
			 * 首先，它会进入_set_routing();然后发现$this->uri->uri_string为空，进入_set_default_controller();
			 * 然后发现在_set_default_controller里，发现$this->default_controller不为FALSE，（＠＠＠＠），然后再
			 * 进入这_set_request()里面，再进入_validate_request()里面，会不会_validate_request里返回空数组？因为
			 * 指定了目录，没有指定控制器，访问默认的，又不存在，然后返回空数组，返回空数组后，最终就会走来你正在看的这个位置，
			 * 然后这个位置再调用_set_default_controller();然后死循环了。。。
			 *
			 * 答案是不会的。
			 * 原因在于：
			 * 我们回到上面解译那个（＠＠＠＠）的地方，在这里，发现$this->default_controller不为FALSE后，它会进入这个else
			 * 里面
			 * else
			 	* {
			 	*  $this->set_class($this->default_controller);   ..............1
			 	*  $this->set_method('index');                 ...................2
			 	*  $this->_set_request(array($this->default_controller, 'index'));  ..........3
			 	* }
			 *
			 * 然后第3行，传入_set_request($segments)中的那个$segments其实是
			 * array('welcome','index')，重点在于那个小小的'index'!!!!!!!
			 * 这样一来，我们进入_validate_request()的时候，我们实质并没有“指定目录但没有指定控制器，访问默认控制器”，
			 * 而是“指定了一个welcome的目录，和一个叫index的控制器！！”，所以才不会死循环。
			 * 如果你试着把第3行那个'index'去掉，那么，一定会死循环！！！！！！！！不信试试！CI太牛逼了，居然这样做。汗。。
			 * 当然，‘index’还有一个作用，就是设置默认方法啦。
			 */
			return;
		}

		if ($this->translate_uri_dashes === TRUE)
		{
			$segments[0] = str_replace('-', '_', $segments[0]);
			if (isset($segments[1]))
			{
				$segments[1] = str_replace('-', '_', $segments[1]);
			}
		}

		$this->set_class($segments[0]);
		if (isset($segments[1]))
		{
			$this->set_method($segments[1]);
		}
		else
		{
			$segments[1] = 'index';
		}

		array_unshift($segments, NULL);
		unset($segments[0]);
		$this->uri->rsegments = $segments;
	}

	// --------------------------------------------------------------------

	/**
	 * Set default controller
	 *
	 * @return	void
	 */
	protected function _set_default_controller()
	{
		//在Router::_set_routing()函数里面有一个操作，是从配置文件里面读取默认控制器名，如果没有就有FALSE。
		if (empty($this->default_controller))
		{
			//如果没有默认的话，就报错，结束程序。
			//实质上，这个_set_default_controller()仅仅是在uri没有指定控制器，要求访问默认控制器的时候才
			//被调用，所以如果连默认控制器都没有，那么可以果断报错。
			show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
		}
		//如果有，下面我们就来把默认的控制器设置为当前要找的路由。
		
		//这里只是分“有指定默认方法”和“没有指定”两种情况而已。不过要弄点下面那个$this->_set_request($x);CI这几个函数
		//也许写得很妙，但是让人看得纠结。。。
		// Is the method being specified?
		if (sscanf($this->default_controller, '%[^/]/%s', $class, $method) !== 2)
		{
			$method = 'index';
		}

		if ( ! file_exists(APPPATH.'controllers/'.$this->directory.ucfirst($class).'.php'))
		{
			// This will trigger 404 later
			return;
		}

		$this->set_class($class);
		$this->set_method($method);

		// Assign routed segments, index starting from 1
		$this->uri->rsegments = array(
			1 => $class,
			2 => $method
		);

		log_message('debug', 'No URI present. Default controller set.');
	}

	// --------------------------------------------------------------------

	/**
	 * Validate request
	 *
	 * Attempts validate the URI request and determine the controller path.
	 *
	 * @used-by	CI_Router::_set_request()
	 * @param	array	$segments	URI segments
	 * @return	mixed	URI segments
	 */
	protected function _validate_request($segments)
	{
		$c = count($segments);
		$directory_override = isset($this->directory);

		// Loop through our segments and return as soon as a controller
		// is found or when such a directory doesn't exist
		while ($c-- > 0)
		{
			$test = $this->directory
				.ucfirst($this->translate_uri_dashes === TRUE ? str_replace('-', '_', $segments[0]) : $segments[0]);

			if ( ! file_exists(APPPATH.'controllers/'.$test.'.php')
				&& $directory_override === FALSE
				&& is_dir(APPPATH.'controllers/'.$this->directory.$segments[0])
			)
			{
				
				$this->set_directory(array_shift($segments), TRUE);
				continue;
			}
			//如果直接在controllers这个目录下找到与第一段相应的控制器名，那就说明找到了控制器，确定路由，返回。
			return $segments;
		}

		// This means that all segments were actually directories
		return $segments;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Routes
	 *
	 * Matches any routes that may exist in the config/routes.php file
	 * against the URI to determine if the class/method need to be remapped.
	 *
	 * @return	void
	 */
	protected function _parse_routes()
	{
		//知道_set_request()是干嘛的之后，下面的条理就比较清晰了。
		// Turn the segment array into a URI string
		$uri = implode('/', $this->uri->segments);

		// Get HTTP verb
		$http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

		/**
		 * CI有路由重定向的功能，重定向的规则和实现就是在这里。
		 */
		// Loop through the route array looking for wildcards
		foreach ($this->routes as $key => $val)
		{
			// Check if route format is using HTTP verbs
			if (is_array($val))
			{
				$val = array_change_key_case($val, CASE_LOWER);
				if (isset($val[$http_verb]))
				{
					$val = $val[$http_verb];
				}
				else
				{
					continue;
				}
			}

			// Convert wildcards to RegEx
			$key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $uri, $matches))
			{
				// Are we using callbacks to process back-references?
				if ( ! is_string($val) && is_callable($val))
				{
					// Remove the original string from the matches array.
					array_shift($matches);

					// Execute the callback using the values in matches as its parameters.
					$val = call_user_func_array($val, $matches);
				}
				// Are we using the default routing method for back-references?
				elseif (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE)
				{
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}

				$this->_set_request(explode('/', $val));
				return;
			}
		}

		// If we got this far it means we didn't encounter a
		// matching route so we'll set the site default route
		$this->_set_request(array_values($this->uri->segments));
	}

	// --------------------------------------------------------------------

	/**
	 * Set class name
	 *
	 * @param	string	$class	Class name
	 * @return	void
	 */
	public function set_class($class)
	{
		$this->class = str_replace(array('/', '.'), '', $class);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the current class
	 *
	 * @deprecated	3.0.0	Read the 'class' property instead
	 * @return	string
	 */
	public function fetch_class()
	{
		return $this->class;
	}

	// --------------------------------------------------------------------

	/**
	 * Set method name
	 *
	 * @param	string	$method	Method name
	 * @return	void
	 */
	public function set_method($method)
	{
		$this->method = $method;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the current method
	 *
	 * @deprecated	3.0.0	Read the 'method' property instead
	 * @return	string
	 */
	public function fetch_method()
	{
		return $this->method;
	}

	// --------------------------------------------------------------------

	/**
	 * Set directory name
	 *
	 * @param	string	$dir	Directory name
	 * @param	bool	$append	Whether we're appending rather than setting the full value
	 * @return	void
	 */
	public function set_directory($dir, $append = FALSE)
	{
		if ($append !== TRUE OR empty($this->directory))
		{
			$this->directory = str_replace('.', '', trim($dir, '/')).'/';
		}
		else
		{
			$this->directory .= str_replace('.', '', trim($dir, '/')).'/';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch directory
	 *
	 * Feches the sub-directory (if any) that contains the requested
	 * controller class.
	 *
	 * @deprecated	3.0.0	Read the 'directory' property instead
	 * @return	string
	 */
	public function fetch_directory()
	{
		return $this->directory;
	}

}
