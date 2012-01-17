<?php

namespace ActiveRedis;

class Database 
{
	protected static $db;
	protected static $default = 0;
	
	/**
	 * Connect an "adapted" Redis interface to ActiveRedis
	 * 
	 * @param mixed $db Redis interface
	 * @param key $name optional
	 */
	public static function adapt($db, $name = null)
	{
		// Eventally this should be smart about using the appropriate adapter based on the class name of $db
		return static::connect(new Adapter($db), $name ?: ++static::$default);
	}
	
	/**
	 * Allow access to an existing database interface or adapter 
	 * via an additional name of your choice.
	 * 
	 * @param key $alias
	 * @param key $actualName
	 */
	public static function alias($alias, $actualName)
	{
		static::$db[$alias] =& static::$db[$actualName];
	}
	
	/**
	 * Connect a raw Redis interface to ActiveRedis
	 * NOTE: It is best to use an adapter for compatibility reasons
	 * 
	 * @see Database::adapt()
	 * @param mixed $db
	 * @param key $name optional
	 * @return bool
	 */
	public static function connect($db, $name = null)
	{
		return static::$db[$name ?: ++static::$default] = $db;
	}
	
	/**
	 * Discard a database interface or adapter by name. If a name
	 * is not given, then the latest default database name will be used.
	 * 
	 * @param key $name optional
	 * @return bool
	 */
	public static function discard($name = null) 
	{
		if (is_null($name)) {
			$name = static::$default;
			if ($name && isset(static::$db[$name])) {
				static::$default--;
			}
		}
		if (isset(static::$db[$name])) { 
			unset(static::$db[$name]);
			return true;
		}
	}
	
	/**
	 * Access an instance of a Redis interface or adapter by name.
	 * If no name is given, the latest default name will be used.
	 * 
	 * @param key $name optional
	 * @return mixed
	 */
	public static function instance($name = null) 
	{
		return static::$db[$name ?: static::$default];
	}
}

?>