<?php

declare(strict_types=1);

namespace ActiveRedis;

class Provider
{
	protected static $dbs = [];

	/**
	 * Get a database instance by name
	 */
	public static function getDatabase(string $name): Database
	{
		if (!isset(self::$dbs[$name])) {
			throw new DatabaseNotFound('Database not found: ' . $name);
		}
		return self::$dbs[$name];
	}

	/**
	 * Name a database instance
	 */
	public static function setDatabase(string $name, Database $db): void
	{
		self::$dbs[$name] = $db;
	}
}