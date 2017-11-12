<?php

// Autoloader
// In case you don't want to use Composer
spl_autoload_register(function($className){

	// Only load for this namespace
	if (strncmp($className, 'ActiveRedis\\', 12) !== 0) {
		return;
	}

	// Simple directory structure based on namespace hierarchy
	if (file_exists($file = __DIR__ . '/lib/' . strtr(substr($className, 6), '\\', '/') . '.php')) {
		require $file;
	}

});
