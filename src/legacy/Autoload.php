<?php

namespace ActiveRedis;

class Autoload 
{
	static $ext = '.php';
	
	static function load($className) 
	{
		$namespace = get_namespace(get_called_class());
		
		// Is related to ActiveRedis?
		if (substr($className, 0, strlen($namespace)) == $namespace) {
			
			// System independent
			$s = DIRECTORY_SEPARATOR;
			
			// Get path via explode/implode
			$classFile = __DIR__ . $s . implode($s, array_slice(explode('\\', trim($className, '\\')), 1)) . static::$ext;
			
			Log::debug(get_called_class() . ' load ' . $className . ', ' . $classFile);
			
			// Get path via string manipulation
			// $classFile = __DIR__ . $s . str_replace('\\', $s, substr($className, strlen(__NAMESPACE__) + 1)) . static::$ext;-
			
			// Do it :] throw an error if it doesn't work
			return include $classFile;
		}
	}
	
	static function register() {
		spl_autoload_register(get_called_class() . '::load');
	}
	
	static function unregister() {
		spl_autoload_unregister(get_called_class() . '::load'); 
	}
}

