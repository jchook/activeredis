<?php

// App directory
define('APP_ROOT', dirname(__DIR__));

// ActiveRedis autoloader
require_once APP_ROOT . '/src/autoload.php';

// ActiveRedisSpec autoloader
spl_autoload_register(function($className) {
	$className = ltrim($className, '\\');
	if (substr($className, 0, 16) === 'ActiveRedisSpec\\') {
		$path = APP_ROOT . '/spec/' . str_replace('\\', '/', substr($className, 16)) . '.php';
		if (file_exists($path)) {
			include $path;
		}
	}
});

// Mock database
ActiveRedis\Network::set('mock', new ActiveRedisSpec\Support\MockDatabase([
	'connection' => new ActiveRedisSpec\Support\MockConnection(),
	'schema' => [
		'tables' => [
			ActiveRedisSpec\Support\MockModel::class => [
				'name' => 'mocks',
				'class' => ActiveRedisSpec\Support\MockTable::class,
			],
		],
	],
]));
