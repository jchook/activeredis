<?php

namespace ActiveRedis;

class Log {
	
	static $function = 'static::log';
	static $prefixes = array(__NAMESPACE__);
	static $passthru = false;
	
	static function __callStatic($fn, $args)
	{
		if (static::$passthru) {
			$log = implode(' ', array_merge((array) static::$prefixes, (array) $args));
			return call_user_func(array(static::$passthru, $fn), $log);
		} else {
			$log = implode(' ', array_merge((array) static::$prefixes, (array) $fn, (array) $args));
			return call_user_func(static::$function, $log);
		}
	}
	
	static function log()
	{
		error_log(implode(' ', func_get_args()));
	}
	
}

?>