<?php

// Register the Autoloader
include __DIR__ . '/lib/Autoload.php';
ActiveRedis\Autoload::register();

include __DIR__ . '/lib/Functions.php';
include __DIR__ . '/lib/Exceptions.php';
include __DIR__ . '/lib/Associations.php';

?>