<?php

if (defined('ActiveRedis\Version')) return;
define('ActiveRedis\Version', '∞');

// Logging :]
include __DIR__ . '/lib/Log.php';

// Register the Autoloader
include __DIR__ . '/lib/Autoload.php';
ActiveRedis\Autoload::register();

// The autoloader will not hit these
include __DIR__ . '/lib/Functions.php';
include __DIR__ . '/lib/Behaviors.php';
include __DIR__ . '/lib/Exceptions.php';
include __DIR__ . '/lib/Associations.php';

?>