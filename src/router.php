<?php
/**
 * @package Router
 * @author Liam Chapman
 */

namespace Router;

class Router {
	
	/**
	 * @var String
	 */
	public $uri; 
	
	/**
	 * @var String
	 */ 
	 public $query_string;
	 
	/**
	 * @var String
	 */ 
	 public $error_document = null;	 
	
	/**
	 * @var Array
	 */
	public $actions = array(); 
	
	/**
	 * @var Array
	 */
	public $call = array();
	
	/**
	 * @var String
	 */
	public $sep = '.';	
	
	/**
	 * @var Array
	 */
	public $request_types = array('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'TRACE', 'CONNECT');
	
	/**
	 * @param $ignore_qs Boolean
	 * - Optional paramater to ignore query string
	 */
	public function __construct ($ignore_qs = true) {
		if (strpos($_SERVER['REQUEST_URI'], '?') !== false && $ignore_qs) {
			list($uri, $query_string) = explode('?', $_SERVER['REQUEST_URI']);						
			$this->uri  		= $uri;
			$this->query_string = $query_string;
		} else {
			$this->uri 			= $_SERVER['REQUEST_URI'];
			$this->query_string = null;
		}
	}
	
	/**
	 * @param $actions Array
	 * - Pass through routes as an array, with key as the URL and value as the action
	 */
	public function actions ($actions = array()) {
		if (!empty($actions)) {
			foreach ($actions as $route => $action) {
				$this->actions[trim($route)] = $action;
			}
		}
	}
	
	/**
	 * This function processes and sorts our actions to be ready to be executed
	 */
	public function process () {
		$return = array();		
		foreach ($this->actions as $route => $actions) {
			if (is_callable($actions)) {
				$return[$route]['closure'] = $actions;
			} else {
				if (strpos($actions, $this->sep)) {
					list($class, $method) 	  = explode($this->sep, $actions);
					$return[$route]['class']  = $class;
					$return[$route]['method'] = $method;
				}
			}
		}
		return $return;
	}
	
	/**
	 * @param $static Boolean - used when called statically to keep running if call is empty.
	 * When ran this function loops through our actions that have been processed and checks if the URL is valid. 
	 * Returns a 404 if nothing is found.
	 */
	public function execute ($static = false) {
		foreach ($this->process() as $route => $callback) {
			$find = '!^'.str_replace(array_keys($this->patterns()), array_values($this->patterns()), $route).'\/?$!';
			if (preg_match($find, $this->uri, $params)) {
				array_shift($params);
				if (isset($callback['closure'])) {
					$this->call['closure'] = $callback['closure'];
					$this->call['params']  = $params;
				} else {
					$this->call['class']  = $callback['class'];
					$this->call['method'] = $callback['method'];
					$this->call['params'] = $params;
				}
				if (in_array($_SERVER['REQUEST_METHOD'], $this->request_types) && isset($this->call['method'])) {					
					$request_method		  = $_SERVER['REQUEST_METHOD'] != 'GET' ? $_SERVER['REQUEST_METHOD'].'_' : '';
					$this->call['method'] = $request_method.$this->call['method'];
				}
			}	
		}
		if (!empty($this->call) || $static) {
			if (isset($this->call['closure'])) {
				call_user_func_array($this->call['closure'], $this->call['params']);
			} else {
				if (isset($this->call['class'])) {
					$class = new $this->call['class']; 
					call_user_func_array(array($class, $this->call['method']), $this->call['params']);
				}
			}			
		} else {
			$this->E404($this->error_document);
		}		
	}
	
	/**
	 * Optional Parameter checks for the URL. 
	 * e.g. /my-uri/:string/:int
	 * - /my-uri/title/1
	 */
	public function patterns () {
		return array(
			':string' 	=> '([^\/]+)',
			':int'		=> '([0-9]+)',
			':any'	  	=> '(.+)'
		);
	}
	
	/**
	 * @param $file String
	 * Default 404 Command returns a default message, but includes option parameter for a file	 
	 */
	public function E404 ($file = null) {
		header('HTTP/1.0 404 Not Found');
		if (is_null($file)) {
			exit("<h1>404 Not Found</h1>\nThe page that you have requested could not be found.");
		} else {
			include_once($file);
			exit;
		}
	}
	
	/**
	 * @param $route String
	 * @param $callback Closure / Function
	 * Direct request instead of using an action array
	 */
	public function request ($route, $callback) {
		$this->actions[trim($route)] = $callback;
	}
	
	/**
	 * Magic method to look for direct request methods
	 * e.g. ->get('/my-uri', function () {}); , ->post('/my-uri', function () {});
	 */
	public function __call ($call, $args) {
		$call = strtoupper($call);
		if (in_array($call, $this->request_types) && $_SERVER['REQUEST_METHOD'] == $call) {
			$this->request($args[0], $args[1]);
		}
	}
	
	/**
	 * Magic method to look for direct request methods statically
	 * e.g. Router::get('/my-uri', function () {}); , Router::post('/my-uri', function () {});
	 * Not truly static...tad hacky. May need to be rethought and built statically.. hmm
	 */
	public static function __callStatic ($call, $args) {
		$call = strtoupper($call);
		$self = new self();
		if (in_array($call, $self->request_types) && $_SERVER['REQUEST_METHOD'] == $call) {
			$self->request($args[0], $args[1]);
			$self->execute(1);
		}
	}
	
}