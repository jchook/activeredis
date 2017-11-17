<?php

declare(strict_types=1);

namespace ActiveRedis;

class Network
{
	protected static $dbs = [];

	/**
	 * Get a database instance by name
	 */
	public static function get(string $name): Database
	{
		if (!isset(self::$dbs[$name])) {
			// Not sure if I should throw here
			// self::$dbs[$name] = new Database();
			throw new DatabaseNotFound('Database not found: ' . $name);
		}
		return self::$dbs[$name];
	}

	/**
	 * Name a database instance
	 */
	public static function set(string $name, Database $db): void
	{
		self::$dbs[$name] = $db;
	}
}