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
 * Exceptions Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Exceptions
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/exceptions.html
 */
class CI_Exceptions {

	/**
	 * Nesting level of the output buffering mechanism
	 *
	 * @var	int
	 */
	public $ob_level;

	/**
	 * List of available error levels
	 *
	 * @var	array
	 */
	public $levels = array(
		E_ERROR			=>	'Error',
		E_WARNING		=>	'Warning',
		E_PARSE			=>	'Parsing Error',
		E_NOTICE		=>	'Notice',
		E_CORE_ERROR		=>	'Core Error',
		E_CORE_WARNING		=>	'Core Warning',
		E_COMPILE_ERROR		=>	'Compile Error',
		E_COMPILE_WARNING	=>	'Compile Warning',
		E_USER_ERROR		=>	'User Error',
		E_USER_WARNING		=>	'User Warning',
		E_USER_NOTICE		=>	'User Notice',
		E_STRICT		=>	'Runtime Notice'
	);

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->ob_level = ob_get_level();
		// Note: Do not log messages from this constructor.
	}

	// --------------------------------------------------------------------

	/**
	 * Exception Logger
	 *
	 * Logs PHP generated error messages
	 *
	 * @param	int	$severity	Log level
	 * @param	string	$message	Error message
	 * @param	string	$filepath	File path
	 * @param	int	$line		Line number
	 * @return	void
	 */
	public function log_exception($severity, $message, $filepath, $line)
	{
		$severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;
		log_message('error', 'Severity: '.$severity.' --> '.$message.' '.$filepath.' '.$line);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Error Handler
	 *
	 * @uses	CI_Exceptions::show_error()
	 *
	 * @param	string	$page		Page URI
	 * @param 	bool	$log_error	Whether to log the error
	 * @return	void
	 */
	public function show_404($page = '', $log_error = TRUE)
	{
		if (is_cli())
		{
			$heading = 'Not Found';
			$message = 'The controller/method pair you requested was not found.';
		}
		else
		{
			$heading = '404 Page Not Found';
			$message = 'The page you requested was not found.';
		}

		// By default we log this, but allow a dev to skip it
		if ($log_error)
		{
			log_message('error', $heading.': '.$page);
		}

		echo $this->show_error($heading, $message, 'error_404', 404);
		exit(4); // EXIT_UNKNOWN_FILE
	}

	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * Takes an error message as input (either as a string or an array)
	 * and displays it using the specified template.
	 *
	 * @param	string		$heading	Page heading
	 * @param	string|string[]	$message	Error message
	 * @param	string		$template	Template name
	 * @param 	int		$status_code	(default: 500)
	 *
	 * @return	string	Error page output
	 */
	/**
	 * 先解释一下show_php_error,show_error,和show_404之间的关系和区别。
	 * show_php_error()是代码本身的一些错误，例如变量未定义之类的，平时我们调试的时候经常见到的一些错误，是不小心写错代码而导致的。
	 * show_error()是有意识触发的错误，不是代码写错，而是代码不当，或者用户操作不当，比如找不到控制器，指定方法之类的，CI就show一
	 * 个错误出来，当然开发者也可以调用此方法响应一个错误信息，某种程度上类似于catch到一个exception之后的处理，然后根据exception
	 * 发出不同的提示信息。
	 * show_404()是show_error()中的一种特殊情况，就是请求不存在的情况，响应一个404错误。
	 */
	/**
	 * General Error Page
	 */
	public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
	{
		$templates_path = config_item('error_views_path');
		if (empty($templates_path))
		{
			$templates_path = VIEWPATH.'errors'.DIRECTORY_SEPARATOR;
		}

		if (is_cli())
		{
			$message = "\t".(is_array($message) ? implode("\n\t", $message) : $message);
			$template = 'cli'.DIRECTORY_SEPARATOR.$template;
		}
		else
		{
			set_status_header($status_code);
			$message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
			$template = 'html'.DIRECTORY_SEPARATOR.$template;
		}

		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		ob_start();
		include($templates_path.$template.'.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	// --------------------------------------------------------------------

	public function show_exception($exception)
	{
		$templates_path = config_item('error_views_path');
		if (empty($templates_path))
		{
			$templates_path = VIEWPATH.'errors'.DIRECTORY_SEPARATOR;
		}

		$message = $exception->getMessage();
		if (empty($message))
		{
			$message = '(null)';
		}

		if (is_cli())
		{
			$templates_path .= 'cli'.DIRECTORY_SEPARATOR;
		}
		else
		{
			set_status_header(500);
			$templates_path .= 'html'.DIRECTORY_SEPARATOR;
		}

		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}

		ob_start();
		include($templates_path.'error_exception.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @param	int	$severity	Error level
	 * @param	string	$message	Error message
	 * @param	string	$filepath	File path
	 * @param	int	$line		Line number
	 * @return	string	Error page output
	 */
	public function show_php_error($severity, $message, $filepath, $line)
	{
		$templates_path = config_item('error_views_path');
		if (empty($templates_path))
		{
			$templates_path = VIEWPATH.'errors'.DIRECTORY_SEPARATOR;
		}

		$severity = isset($this->levels[$severity]) ? $this->levels[$severity] : $severity;

		// For safety reasons we don't show the full file path in non-CLI requests
		if ( ! is_cli())
		{
			$filepath = str_replace('\\', '/', $filepath);
			if (FALSE !== strpos($filepath, '/'))
			{
				$x = explode('/', $filepath);
				$filepath = $x[count($x)-2].'/'.end($x);
			}

			$template = 'html'.DIRECTORY_SEPARATOR.'error_php';
		}
		else
		{
			$template = 'cli'.DIRECTORY_SEPARATOR.'error_php';
		}

		/*
		 * 如果还没看过core/Loader.php，下面这个判断可能让人有点迷惑。
		* ob_get_level()是取得当前缓冲机制的嵌套级别。（缓冲是可以一层嵌一层的。）
		* 右边的$this->ob_level是在__construct()里面同样通过ob_get_level()被赋值的。
		* 也就是说，有可能出现：Exception组件被加载时（也就是应用刚开始运行时）的缓冲级别（其实也就是程序最开始的时候的缓冲级别，那时
		* 候是还没有ob_start()过的），与发生错误的时候的缓冲级别相差1。
		* 在控制器执行$this->load->view("xxx");的时候，实质，Loader引入并执行这个视图文件的时候，是先把缓冲打开，即
		* 先ob_start()，所有输出放到缓冲区（详见：core/Loader.php中的_ci_load()）,然后再由Output处理输出。
		* 因此，如果是在视图文件发生错误，则就会出现缓冲级别相差1的情况，此时先把输出的内容给flush出来，然后再把错误信息输出。
		*
		*/
		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		ob_start();
		include($templates_path.$template.'.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

}
