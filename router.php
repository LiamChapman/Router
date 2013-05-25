<?php

class Router {
	
	public $uri, $actions = array(), $call = array();
	public $sep = '.';
	
	public $alt_request_types = array('POST', 'HEAD', 'PUT', 'DELETE', 'TRACE', 'CONNECT'); //default GET
	
	public function __construct($ignore_qs = false) {
		$this->uri = $ignore_qs ? str_replace('?'. $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) : $_SERVER['REQUEST_URI'];		
	}
	
	public function actions($actions) {
		$this->actions = $actions;
	}
	
	public function process () {
		$return = array();
		foreach ($this->actions as $route => $actions) {
			if (strpos($actions, $this->sep)) {
				list($class, $method) 	  = explode($this->sep, $actions);
				$return[$route]['class']  = $class;
				$return[$route]['method'] = $method;
			}
		}
		return $return;
	}
	
	public function execute () {
		$patterns = $this->patterns();
		foreach ($this->process() as $route => $callback) {
			$find = '!^'.str_replace(array_keys($patterns), array_values($patterns), $route).'\/?$!';
			if (preg_match($find, $this->uri, $params)) {
				array_shift($params);
				$this->call['class']  = $callback['class'];
				$this->call['method'] = $callback['method'];
				$this->call['params'] = $params;
				# Check for request method if set. All methods default with GET so no need to append it
				if (in_array($_SERVER['REQUEST_METHOD'], $this->alt_request_types)) {
					$this->call['method'] = $_SERVER['REQUEST_METHOD'].'_'.$this->call['method'];
				}
			}	
		}
		if (!empty($this->call)) {
			call_user_func_array(array($this->call['class'], $this->call['method']), $this->call['params']);
		} else {
			$this->E404();
		}		
	}
	
	public function patterns () {
		return array(
			':string' 	=> '([^\/]+)',
			':int'		=> '([0-9]+)',
			':any'	  	=> '(.+)'
		);
	}
	
	public function E404 () {
		header($_ENV['SERVER_PROTOCOL']." 404 Not Found", true, 404);
		exit('404 Error');
	}
	
}