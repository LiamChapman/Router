<?php
/**
 * @package Router
 * @author Liam Chapman
 */
class Router {
	
	/**
	 * @var String
	 */
	public $uri; 
	
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
	public $request_types = array('GET','POST', 'HEAD', 'PUT', 'DELETE', 'TRACE', 'CONNECT');
	
	/**
	 * @param $ignore_qs Boolean
	 * - Optional paramater to ignore quesry string
	 */
	public function __construct ($ignore_qs = false) {
		$this->uri = $ignore_qs ? str_replace('?'. $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) : $_SERVER['REQUEST_URI'];	
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
	 * When ran this function loops through our actions that have been processed and checks if the URL is valid. 
	 * Returns a 404 if nothing is found.
	 */
	public function execute () {
		$patterns = $this->patterns();
		foreach ($this->process() as $route => $callback) {
			$find = '!^'.str_replace(array_keys($patterns), array_values($patterns), $route).'\/?$!';
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
		if (!empty($this->call)) {
			if (isset($this->call['closure'])) {
				call_user_func_array($this->call['closure'], $this->call['params']);
			} else {
				$class = new $this->call['class']; 
				call_user_func_array(array($class, $this->call['method']), $this->call['params']);
			}
		} else {
			$this->E404();
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
		if (is_callable($callback)) {
			$this->actions[trim($route)] = $callback;
		}
	}
	
	/**
	 * Magic method to look for direct request methods
	 * e.g. ->get('/my-uri', function () {}); , ->post('/my-uri', function (){});
	 */
	public function __call ($call, $args) {
		$call = strtoupper($call);
		if (in_array($call, $this->request_types) && $_SERVER['REQUEST_METHOD'] == $call) {
			$this->request($args[0], $args[1]);
		}
	}
	
}