<?php

namespace ActiveRedis;

class Exception extends \Exception {
	
	static $defaultMessage;
	
	public function __construct($message = null, $code = 0, Exception $previous = null) {
		if (is_null($message)) {
			$message = $this::$defaultMessage;
		}
		parent::__construct($message, $code, $previous);
	}
}

class AttributeNotFound extends Exception {}

class UserException extends Exception {
	static $defaultMessage = 'An unknown error has occurred';
}

class NotFound extends UserException {
	static $defaultMessage = 'The requested resource was not found';
}

class Invalid extends UserException {
	static $defaultMessage = 'Invalid request';
}

class Duplicate extends Invalid {
	static $defaultMessage = 'Duplicate entry';
}

?>