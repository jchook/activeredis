<?php

namespace ActiveRedis;

class Database 
{	
	protected static $db;
	
	public static function adapt($db, $name = 0)
	{
		return static::connect(new Adapter($db), $name);
	}
	
	public static function connect($db, $name = 0)
	{
		return static::$db[$name] = $db;
	}
	
	public static function instance($name = 0) 
	{
		return static::$db[$name];
	}
}

?>