<?php

namespace ActiveRedis;

function get_namespace($class, $start = 0, $length = -1) 
{
	$className = is_object($class) ? get_class($class) : $class;
	return implode('\\', array_slice(explode('\\', $className), $start, $length));
}

function get_class_basename($class)
{
	return array_pop(explode('\\', get_class($this)));
}

function get_public_object_vars($object) 
{
	return \get_object_vars($object);
}

function get_public_class_vars($class)
{
	return \get_class_vars($class);
}

/**
 * This is different from (array) $anything
 * in case $anything is an object.
 */
function array_force($anything)
{
	if (is_array($anything)) {
		return $anything;
	}
	if (is_null($anything)) {
		return array();
	}
	return array($anything);
}

/**
 * Flatten nested arrays into the top-level array
 */
function array_flatten(array $array) 
{
	$i = 0;
	foreach ($array as $element) {
		if (is_array($element)) {
			array_splice($array, $i, 1, $element);
		} else {
			$i++;
		}
	}
	return $array;
}

function array_unique(array $ra) 
{
	$found = array();
	foreach ($ra as $key => $val)
	{
		$found[json_encode($val)] = true;
	}
	return array_keys($found);
}

function array_estrange(array $ra, $includeNumbers = false)
{
	$return = array();
	foreach ($ra as $key => $value)
	{
		if ($includeNumbers || !is_numeric($key)) {
			$return[] = $key;
		}
		$return[] = $value;
	}
	return $return;
}

function array_blend($a, &$b)
{
	foreach ($b as $index => $element) {
		if (isset($a[$index]) && is_array($a[$index]) && is_array($b[$index])) {
			$a[$index] = array_blend($a[$index], $b[$index]);
		} else {
			$a[$index] = $b[$index];
		}
	}
	return $a;
}


