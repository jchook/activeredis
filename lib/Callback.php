<?php

namespace ActiveRedis;

class Callback {
	
	public $args;
	public $callback;
	
	function __construct($callback, $args = null) {
		$this->callback = $callback;
		$this->args = $args;
	}
	
	function __invoke($args) {
		return call_user_func_array($this->callback, array_merge($args, (array) $this->args));
	}
	
	function healthy() {
		return is_callable($this->callback);
	}
	
}

?>