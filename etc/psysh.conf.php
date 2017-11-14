<?php

$root = dirname(__DIR__);

// Composer-ready
if (is_file($root . '/vendor/autoload.php')) {
  echo "Using composer\n";
  require_once $root . '/vendor/autoload.php';
}

// Fallback to normal autoloader
elseif (is_file($root . '/src/autoload.php')) {
  echo "Using " . $root . "/src/autoload.php\n";
  require_once $root . '/src/autoload.php';
}