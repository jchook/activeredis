<?php

namespace ActiveRedis {
	
	function get_namespace($class, $start = 0, $length = -1) 
	{
		$className = is_object($class) ? get_class($class) : $class;
		return implode('\\', array_slice(explode('\\', $className), $start, $length));
	}
	
}

namespace {

}

?>