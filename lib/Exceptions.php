<?php

namespace ActiveRedis;

class Exception extends \Exception {}

class AttributeNotFound extends Exception {}

class UserException extends Exception {}

class NotFound extends UserException {}

class Invalid extends UserException {}
	
?>