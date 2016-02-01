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
 * 上面：
 * 这个BASEPATH，就是在入口文件(index.php)里面定义的那个BASEPATH～
 * 如果没有定义BASEPATH，那么直接退出，下面程序都不执行。其实除了入口文件index.php开头没有这句话之外，所有文件都会有这句话
 * 也就是说，所有文件都不能单独运行，一定是index.php在运行过程中把这些文件通
 * 过某种方式引进来运行，所以只有入口文件index.php才能被访问。
 *
 */
/**
 * 弱弱地建议：
 *  其实把CodeIgniter.php这个文件的代码运行一次，就是整个CI应用都完成了一次完整的运作流程了。
 *  其中会加载一些组件，引入很多外部文件，等等。所以建议在阅读此文件代码的时候，第一遍先阅读它的
 *  大概流程，也就是说不必进入相应的组件、函数文件中去。第二遍看的时候才具体看那些函数、组件里面是怎
 *  么实现的。当然要看个人需要咯～
 *
 */
/**
 * System Initialization File
 *
 * Loads the base classes and executes the request.
 *
 * @package		CodeIgniter
 * @subpackage	CodeIgniter
 * @category	Front-controller
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/
 */

/**
 * CodeIgniter Version
 *
 * @var	string
 *
 */
//定义CI版本
	define('CI_VERSION', '3.0.2');
//echo BASEPATH;///data/ci3/system///系统目录
//echo APPPATH;///data/ci3/application///应用目录
/*
 * ------------------------------------------------------
 *  Load the framework constants
 * ------------------------------------------------------
 */
	//echo APPPATH.'config/'.ENVIRONMENT.'/constants.php';exit();
	//路径：application/config/development/constants.php
	//constants.php是系统常量文件的配置
	//加载配置的常量。这个配置文件里面默认已经有一些和文件有关的常量。
 
    //下面这个判断可以看出一开始我们在index.php里面定义的那个ENVIRONMENT的作用之一，如果是定义某个环境，
    //会调用相应的配置文件，这样就可以使得应用在相应的环境中运行。不仅仅是这个常量的配置文件是这样子，
    //以后你会发现，其实全部配置文件都是先判断当前环境再引入。
    //方便切换，只需在index.php里面改一下ENVIRONMENT的值。
	//当然啦，如果压根没有这个环境下的配置文件，就会调用默认的。CI手册上也有说，各种环境下的相同的配置文件，可以直接放在
	//config/下，而不需要每个环境的目录下都有。
	if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/constants.php'))
	{
		require_once(APPPATH.'config/'.ENVIRONMENT.'/constants.php');
	}
	require_once(APPPATH.'config/constants.php');
/*
 * ------------------------------------------------------
 *  Load the global functions
 * ------------------------------------------------------
 */
	//Common.php包括很多全局函数
	require_once(BASEPATH.'core/Common.php');


/*
 * ------------------------------------------------------
 * Security procedures
 * ------------------------------------------------------
 */

if ( ! is_php('5.4'))
{
	//这个magic_quotes就是会自动把那些由$_GET,$_POST等传过来的值进行处理，加\。
	//这个东西最好不要打开，虽然某些时候会帮到忙，不过过滤、转义等处理最后还是手动做好一些。
	//php5.3以上默认是把这个东西关掉的(linux下/etc/php.ini里面)。这个东西本来就不应该有。
	//ini_set：为一个配置选项设置值
	ini_set('magic_quotes_runtime', 0);

	if ((bool) ini_get('register_globals'))
	{
		$_protected = array(
			'_SERVER',
			'_GET',
			'_POST',
			'_FILES',
			'_REQUEST',
			'_SESSION',
			'_ENV',
			'_COOKIE',
			'GLOBALS',
			'HTTP_RAW_POST_DATA',
			'system_path',
			'application_folder',
			'view_folder',
			'_protected',
			'_registered'
		);

		$_registered = ini_get('variables_order');
		foreach (array('E' => '_ENV', 'G' => '_GET', 'P' => '_POST', 'C' => '_COOKIE', 'S' => '_SERVER') as $key => $superglobal)
		{
			if (strpos($_registered, $key) === FALSE)
			{
				continue;
			}

			foreach (array_keys($$superglobal) as $var)
			{
				if (isset($GLOBALS[$var]) && ! in_array($var, $_protected, TRUE))
				{
					$GLOBALS[$var] = NULL;
				}
			}
		}
	}
}


/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
	set_error_handler('_error_handler');
	set_exception_handler('_exception_handler');
	register_shutdown_function('_shutdown_handler');

/*
 * ------------------------------------------------------
 *  Set the subclass_prefix
 * ------------------------------------------------------
 *
 * Normally the "subclass_prefix" is set in the config file.
 * The subclass prefix allows CI to know if a core class is
 * being extended via a library in the local application
 * "libraries" folder. Since CI allows config items to be
 * overridden via data set in the main index.php file,
 * before proceeding we need to know if a subclass_prefix
 * override exists. If so, we will set this value now,
 * before any classes are loaded
 * Note: Since the config file data is cached it doesn't
 * hurt to load it here.
 */
	if ( ! empty($assign_to_config['subclass_prefix']))
	{
	    //这个get_config($replace)就是从配置文件里面读取信息，这里是读取config/config.php中的配置信息
	    //这个参数$replace的作用是什么呢？就是临时把修改配置文件的意思，注意并没有从改变文件的值，这个改变只是
	    //停留在内存的层面上。
	    //而$assign_to_config['xxx'];是在index.php中定义的一个配置信息数组，这个配置数组要优先权要大于配置文件当中的。
	    //所以这个判断的作用是，看看有没有在index.php里面定义了 $assign_to_config['subclass_prefix']，如果有的话，
	    //就那把配置文件中的$config['subclass_prefix']的值改成这个。
		get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
	}

/*
 * ------------------------------------------------------
 *  Should we use a Composer autoloader?
 * ------------------------------------------------------
 */
	
	if ($composer_autoload = config_item('composer_autoload'))
	{
		if ($composer_autoload === TRUE)
		{
			file_exists(APPPATH.'vendor/autoload.php')
				? require_once(APPPATH.'vendor/autoload.php')
				: log_message('error', '$config[\'composer_autoload\'] is set to TRUE but '.APPPATH.'vendor/autoload.php was not found.');
		}
		elseif (file_exists($composer_autoload))
		{
			require_once($composer_autoload);
		}
		else
		{
			log_message('error', 'Could not find the specified $config[\'composer_autoload\'] path: '.$composer_autoload);
		}
	}

/*
 * ------------------------------------------------------
 *  Start the timer... tick tock tick tock...
 * ------------------------------------------------------
 */
	//load_class()是用来加载类的，准确来说，是用来取得某个类的一个单一实例。
	//像下来的调用就是返回system/core/Benchmark的一个实例。core里面的大都是CI的组件。
	$BM =& load_class('Benchmark', 'core');
	$BM->mark('total_execution_time_start');
	$BM->mark('loading_time:_base_classes_start');

 /**
  * 这个hook也是非常非常棒的一个东西！它可以让我们很好地扩展和改造CI～
  * 可以这样理解，一个应用从运行到结束这个期间，CI为我们保留了一些位置，在这些位置上面可以让开发人员放上所谓的
  * “钩子”（其实就是一段程序啦！），在应用运行过程中，当运行到有可以放钩子的位置的时候，先检测开发人员有没有
  * 实现这里的钩子，如果有就运行它。
  * 有些地方甚至可以用自己写的钩子程序替代CI框架本来的程序。
  */
	$EXT =& load_class('Hooks', 'core');

/*
 * ------------------------------------------------------
 *  Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
	//这里就有一个钩子啦。大概意思是：整个应用系统开始动了，这里要不要先让开发人员来一段程序？
	//如果你定义了pre_system这个钩子，那么，其实它就是在这个位置运行的。
	$EXT->call_hook('pre_system');

/*
 * ------------------------------------------------------
 *  Instantiate the config class
 * ------------------------------------------------------
 *
 * Note: It is important that Config is loaded first as
 * most other classes depend on it either directly or by
 * depending on another class that uses it.
 *
 */
	//取得配置组件。
	$CFG =& load_class('Config', 'core');

	// Do we have any manually set config items in the index.php file?
	//如果有在index.php定义配置数组，那么就丢给配置组件CFG，以后就由CFG来保管了配置信息了。
	if (isset($assign_to_config) && is_array($assign_to_config))
	{
		foreach ($assign_to_config as $key => $value)
		{
			$CFG->set_item($key, $value);
		}
	}

/*
 * ------------------------------------------------------
 * Important charset-related stuff
 * ------------------------------------------------------
 *
 * Configure mbstring and/or iconv if they are enabled
 * and set MB_ENABLED and ICONV_ENABLED constants, so
 * that we don't repeatedly do extension_loaded() or
 * function_exists() calls.
 *
 * Note: UTF-8 class depends on this. It used to be done
 * in it's constructor, but it's _not_ class-specific.
 *
 */
	$charset = strtoupper(config_item('charset'));
	ini_set('default_charset', $charset);

	if (extension_loaded('mbstring'))
	{
		define('MB_ENABLED', TRUE);
		// mbstring.internal_encoding is deprecated starting with PHP 5.6
		// and it's usage triggers E_DEPRECATED messages.
		@ini_set('mbstring.internal_encoding', $charset);
		// This is required for mb_convert_encoding() to strip invalid characters.
		// That's utilized by CI_Utf8, but it's also done for consistency with iconv.
		mb_substitute_character('none');
	}
	else
	{
		define('MB_ENABLED', FALSE);
	}

	// There's an ICONV_IMPL constant, but the PHP manual says that using
	// iconv's predefined constants is "strongly discouraged".
	if (extension_loaded('iconv'))
	{
		define('ICONV_ENABLED', TRUE);
		// iconv.internal_encoding is deprecated starting with PHP 5.6
		// and it's usage triggers E_DEPRECATED messages.
		@ini_set('iconv.internal_encoding', $charset);
	}
	else
	{
		define('ICONV_ENABLED', FALSE);
	}

	if (is_php('5.6'))
	{
		ini_set('php.internal_encoding', $charset);
	}

/*
 * ------------------------------------------------------
 *  Load compatibility features
 * ------------------------------------------------------
 */

	require_once(BASEPATH.'core/compat/mbstring.php');
	require_once(BASEPATH.'core/compat/hash.php');
	require_once(BASEPATH.'core/compat/password.php');
	require_once(BASEPATH.'core/compat/standard.php');

/*
 * ------------------------------------------------------
 *  Instantiate the UTF-8 class
 * ------------------------------------------------------
 */
	//取得UTF－8组件
	$UNI =& load_class('Utf8', 'core');

/*
 * ------------------------------------------------------
 *  Instantiate the URI class
 * ------------------------------------------------------
 */
	//取得URI组件。
	$URI =& load_class('URI', 'core');

/*
 * ------------------------------------------------------
 *  Instantiate the routing class and set the routing
 * ------------------------------------------------------
 */
	//取得URI的好基友RTR, 
	//RTR的这个_set_routing();其实做了非常多的事情。。详见core/Router.php。非常重要。
	//这个$routing是在index.php入口文件中可以配置的一个数组。这里起到路由覆盖的作用。
	//index.php里面配置的信息永远都是最优先的。
	//在这里无论你请求的路由是什么，只要有配置$routing（当然要配对），就会被它重定向。
	//所以我觉得这句话放在这个地方有点坑，上面_set_routing搞了那么久，一下子就被覆盖掉了。
	$RTR =& load_class('Router', 'core', isset($routing) ? $routing : NULL);

/*
 * ------------------------------------------------------
 *  Instantiate the output class
 * ------------------------------------------------------
 */
	//输出组件。这个输出组件有什么用？输出不是$this->load->view()么？其实它们两个也是好基友。
	//详见：core/Output.php core/Loader.php
	$OUT =& load_class('Output', 'core');

/*
 * ------------------------------------------------------
 *	Is there a valid cache file? If so, we're done...
 * ------------------------------------------------------
 */
	//下面是输出缓存的处理，这里允许我们自己写个hook来取替代CI原来Output类的缓存输出。//如果可以输出缓存，那么就没有必要再做其它事了。输出结果后直接退出。
	if ($EXT->call_hook('cache_override') === FALSE && $OUT->_display_cache($CFG, $URI) === TRUE)
	{
		exit;
	}

/*
 * -----------------------------------------------------
 * Load the security class for xss and csrf support
 * -----------------------------------------------------
 */
	//取得安全组件（安全组件暂时不详讲，因为对于CI一个运作流程来说，它不是必要的。CI的安全处理以后会作为一个新话题来探讨）
	$SEC =& load_class('Security', 'core');

/*
 * ------------------------------------------------------
 *  Load the Input class and sanitize globals
 * ------------------------------------------------------
 */
	//取得安全组件的好基友INPUT组件。（主要是结合安全组件作一些输入方面的安全处理，$this->input->post()这些常用的操作都是
	//由它们两个负责的。）
	$IN	=& load_class('Input', 'core');

/*
 * ------------------------------------------------------
 *  Load the Language class
 * ------------------------------------------------------
 */
	//语言组件。
	$LANG =& load_class('Lang', 'core');

/*
 * ------------------------------------------------------
 *  Load the app controller and local controller
 * ------------------------------------------------------
 *
 */
	// Load the base controller class
	//引入控制器父类文件。这里和其它组件的引入方式不一样，用load_class();因为我们最终用到的并不是这个父类，
	//而是我们自己写在application/controllers/下的某个由uri请求的控制器。
	require_once BASEPATH.'core/Controller.php';

	/**
	 * Reference to the CI_Controller method.
	 *
	 * Returns current CI instance object
	 *
	 * @return object
	 */
	//定义get_instance();方法，通过调用CI_Controller::get_instance()可以实现单例化，
	//调用此函数可方便以后直接取得当前应用控制器。
	function &get_instance()
	{
		return CI_Controller::get_instance();
	}
	//和其它组件一样，控制器父类同样可以通过前缀的方式进行扩展。
	if (file_exists(APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller.php'))
	{
		require_once APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller.php';
	}

	// Set a mark point for benchmarking
	$BM->mark('loading_time:_base_classes_end');

/*
 * ------------------------------------------------------
 *  Sanity checks
 * ------------------------------------------------------
 *
 *  The Router class has already validated the request,
 *  leaving us with 3 options here:
 *
 *	1) an empty class name, if we reached the default
 *	   controller, but it didn't exist;
 *	2) a query string which doesn't go through a
 *	   file_exists() check
 *	3) a regular request for a non-existing page
 *
 *  We handle all of these as a 404 error.
 *
 *  Furthermore, none of the methods in the app controller
 *  or the loader class can be called via the URI, nor can
 *  controller methods that begin with an underscore.
 */

	$e404 = FALSE;
	$class = ucfirst($RTR->class);
	$method = $RTR->method;
	if (empty($class) OR ! file_exists(APPPATH.'controllers/'.$RTR->directory.$class.'.php'))
	{
	    //其实如果能够进入这里，说明了上面的$RTR->_set_routing();在_validate_request()的时候一定是在请求默认控制器。
	    //详见：core/Router.php
		$e404 = TRUE;
	}
	else
	{
		require_once(APPPATH.'controllers/'.$RTR->directory.$class.'.php');

		if ( ! class_exists($class, FALSE) OR $method[0] === '_' OR method_exists('CI_Controller', $method))
		{
		    
			$e404 = TRUE;
		}
		/*
		 * ------------------------------------------------------
		*  Security check
		* ------------------------------------------------------
		*
		*  下面主要进行一些方法上的验证。
		*  因为毕竟我们是通过URI直接调用控制器里面的方法的，其实这是个很危险的事情。
		*  必须要保证我们原本没想过要通过URI访问的方法不能访问。
		*
		*  CI里面规定以_下划线开头的方法，一般是作为非公开的方法，即使方法定义为public。
		*  其实不仅仅是CI这么做，把非公开的方法名以_开头，是很好的一种规范。
		*  第二个就是父类CI_Controller里面的方法也是不允许通过URI访问的。
		*  如果URI请求这样的方法，那么会作为404处理。
		*/
		elseif (method_exists($class, '_remap'))
		{
			$params = array($method, array_slice($URI->rsegments, 2));
			$method = '_remap';
		}
		// WARNING: It appears that there are issues with is_callable() even in PHP 5.2!
		// Furthermore, there are bug reports and feature/change requests related to it
		// that make it unreliable to use in this context. Please, DO NOT change this
		// work-around until a better alternative is available.
		elseif ( ! in_array(strtolower($method), array_map('strtolower', get_class_methods($class)), TRUE))
		{
			$e404 = TRUE;
		}
	}

	if ($e404)
	{
		if ( ! empty($RTR->routes['404_override']))
		{
			if (sscanf($RTR->routes['404_override'], '%[^/]/%s', $error_class, $error_method) !== 2)
			{
				$error_method = 'index';
			}

			$error_class = ucfirst($error_class);

			if ( ! class_exists($error_class, FALSE))
			{
				if (file_exists(APPPATH.'controllers/'.$RTR->directory.$error_class.'.php'))
				{
					require_once(APPPATH.'controllers/'.$RTR->directory.$error_class.'.php');
					$e404 = ! class_exists($error_class, FALSE);
				}
				// Were we in a directory? If so, check for a global override
				elseif ( ! empty($RTR->directory) && file_exists(APPPATH.'controllers/'.$error_class.'.php'))
				{
					require_once(APPPATH.'controllers/'.$error_class.'.php');
					if (($e404 = ! class_exists($error_class, FALSE)) === FALSE)
					{
						$RTR->directory = '';
					}
				}
			}
			else
			{
				$e404 = FALSE;
			}
		}

		// Did we reset the $e404 flag? If so, set the rsegments, starting from index 1
		if ( ! $e404)
		{
			$class = $error_class;
			$method = $error_method;

			$URI->rsegments = array(
				1 => $class,
				2 => $method
			);
		}
		else
		{
			show_404($RTR->directory.$class.'/'.$method);
		}
	}

	if ($method !== '_remap')
	{
		$params = array_slice($URI->rsegments, 2);
	}

/*
 * ------------------------------------------------------
 *  Is there a "pre_controller" hook?
 * ------------------------------------------------------
 */
	//这里又一个钩子，这个钩子的位置往往都是在一些特殊的位置的，像这里就是发生在实例化控制器前。
	$EXT->call_hook('pre_controller');

/*
 * ------------------------------------------------------
 *  Instantiate the requested controller
 * ------------------------------------------------------
 */
	// Mark a start point so we can benchmark the controller
	$BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_start');

	//折腾了这么久，终于实例化我们想要的控制器
	$CI = new $class();

/*
 * ------------------------------------------------------
 *  Is there a "post_controller_constructor" hook?
 * ------------------------------------------------------
 */
	$EXT->call_hook('post_controller_constructor');

/*
 * ------------------------------------------------------
 *  Call the requested method
 * ------------------------------------------------------
 */
	call_user_func_array(array(&$CI, $method), $params);

	// Mark a benchmark end point
	$BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_end');

/*
 * ------------------------------------------------------
 *  Is there a "post_controller" hook?
 * ------------------------------------------------------
 */
	$EXT->call_hook('post_controller');

/*
 * ------------------------------------------------------
 *  Send the final rendered output to the browser
 * ------------------------------------------------------
 */
	if ($EXT->call_hook('display_override') === FALSE)
	{
		$OUT->_display();//这里，把$this->load->view();里面缓冲的输出结果输出，基本上一个流程总算完成了。详见core/Output.php。
	}

/*
 * ------------------------------------------------------
 *  Is there a "post_system" hook?
 * ------------------------------------------------------
 */
	$EXT->call_hook('post_system');
