<?php

namespace ActiveRedis;

class Database 
{	
	protected static $db;
	protected static $default;
	
	public static function adapt($db, $name = null)
	{
		return static::connect(new Adapter($db), $name ?: ++static::$default);
	}
	
	public static function connect($db, $name = null)
	{
		return static::$db[$name ?: ++static::$default] = $db;
	}
	
	public static function instance($name = null) 
	{
		return static::$db[$name ?: static::$default];
	}
	
	public static function discard($name = null) {
		$name = $name ?: static::$default;
		if ($name == static::$default) {
			static::$default--;
		}
		unset($db[$name]);
	}
}

?>