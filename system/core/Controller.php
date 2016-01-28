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
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/general/controllers.html
 */
class CI_Controller {

	/**
	 * Reference to the CI singleton
	 *
	 * @var	object
	 */
	private static $instance;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
	    //通过self::$instance实现单例化，在第一次实例时，这个静态变量实质就是引用了这个实例。
	    //以后都可以通过&get_instance();来获得这个单一实例。
	    //user controller集成ci_control,$this就是user对象
		self::$instance =& $this;
		// Assign(分配) all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		
		//var_dump(is_loaded()):
		//把目前程序已经加载的所有的组件都给这个超级控制器来掌管。
		/*以下是11个组件
		array(11) { 
		    ["benchmark"]=> string(9)"Benchmark" 
		    ["hooks"]=> string(5) "Hooks" 
		    ["config"]=> string(6) "Config" 
		    ["log"]=> string(3) "Log" 
		    ["utf8"]=> string(4) "Utf8" 
		    ["uri"]=> string(3) "URI" 
		    ["router"]=> string(6) "Router" 
		    ["output"]=> string(6) "Output" 
		    ["security"]=> string(8) "Security" 
		    ["input"]=> string(5) "Input" 
		    ["lang"]=> string(4) "Lang"
		}
		*/
		foreach (is_loaded() as $var => $class)
		{
		    //echo $class;exit();  //Benchmark
			$this->$var =& load_class($class);//加载组件,其实就是加载core中对应的组件php文件
			//var_dump($this->$var);exit();
			//结果:object(CI_Benchmark)#1 (1) { ["marker"]=> array(4) { ["total_execution_time_start"]=> float(1453869160.7867) ["loading_time:_base_classes_start"]=> float(1453869160.7868) ["loading_time:_base_classes_end"]=> float(1453869160.8501) ["controller_execution_time_( User / login )_start"]=> float(1453869160.8523) } }
			//exit();
		}
		$this->load =& load_class('Loader', 'core');//加载load组件,为什么不像上面那样加载呢?
		//var_dump($this->load);exit();
		//初始化Loader组件，详细Loader.php
		$this->load->initialize();//会把需要加载的内容全部提前加载完毕
		log_message('info', 'Controller Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Get the CI singleton
	 *
	 * @static
	 * @return	object
	 */
	public static function &get_instance()
	{
		return self::$instance;
	}

}
