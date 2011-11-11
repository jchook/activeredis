<?php

// Register the Autoloader, if necessary
if (!class_exists('ActiveRedis\Autoload')) {
	include __DIR__ . '/lib/Autoload.php';
	ActiveRedis\Autoload::register();
}

// Multi-Class Includes
include __DIR__ . '/lib/Exceptions.php';
include __DIR__ . '/lib/Associations.php';

?>