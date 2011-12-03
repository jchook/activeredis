<?php

namespace ActiveRedis;

class Exception extends \Exception {
	public function __construct($message = null, $code = 0, Exception $previous = null) {
		if (is_null($message)) {
			$message = array_pop(explode('\\', get_class($this)));
		}
		parent::__construct($message, $code, $previous);
	}
}

class AttributeNotFound extends Exception {}

class UserException extends Exception {}

class NotFound extends UserException {}

class Invalid extends UserException {}
	
?>