<?php

namespace ActiveRedis {
	
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
	
}

namespace {

}

?>