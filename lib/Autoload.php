<?php

namespace ActiveRedis;

class Autoload 
{
	static function load($className) 
	{
		// Is related to ActiveRedis?
		if (substr_compare($className, __NAMESPACE__) == 0) {
			
			// System independent
			$s = DIRECTORY_SEPARATOR;
			
			// Get path via explode/implode
			$classFile = __DIR__ . $s . implode($s, array_slice(explode('\\', trim($className, '\\')), 1));
			
			// Get path based via string manipulation
			// $classFile = __DIR__ . $s . str_replace('\\', $s, substr($className, strlen(__NAMESPACE__) + 1));
			
			// Do it :] throw an error if it doesn't work
			return include $classFile;
		}
	}
	
	static function register() {
		spl_autoload_register(array(get_called_class(), 'load'));
	}
	
	static function unregister() {
		spl_autoload_unregister(array(get_called_class(), 'load')); 
	}
}

?>

?>