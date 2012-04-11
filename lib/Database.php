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
		return static::add(new Adapter($db), $name ?: ++static::$default);
	}
	
	/**
	 * Allow access to an existing database interface or adapter 
	 * via an additional name of your choice.
	 * 
	 * @param key $alias
	 * @param key $actualName
	 */
	public static function alias($alias, $actualName = null)
	{
		static::$db[$alias] =& static::$db[$actualName ?: static::$default];
	}
	
	/**
	 * Add a raw Redis interface object to ActiveRedis
	 * NOTE: It is best to use an adapter for compatibility reasons
	 * 
	 * @see Database::adapt()
	 * @param mixed $db
	 * @param key $name optional
	 * @return bool
	 */
	public static function add($db, $name = null)
	{
		return static::$db[$name ?: ++static::$default] = $db;
	}
	
	/**
	 * Connect to Redis
	 * 
	 * @param string|array $config ex: '127.0.0.1:6379'
	 * @param string|null $name the instance name of the database connection
	 * @return Connection|null
	 */
	public static function connect($config = null, $name = null)
	{
		if ($config) {
			if (strlen($config)) {
				
				// attempt to parse the url...
				$config = parse_url($config);
				
				// hmm, no scheme? try again.
				if (!isset($config['scheme'])) {
					$config = parse_url('tcp://' . $config);
				}
			}
		}
		return static::add(new Connection(array_merge(array(
			'scheme' => 'tcp',
			'host' => '127.0.0.1',
			'port' => 6379,
		), (array) $config)), $name);
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