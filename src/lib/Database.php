<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Behavior\AbstractBehavior;
use ActiveRedis\Behavior\Index;
use ActiveRedis\Exception\ClassNotFound;
use ActiveRedis\Exception\DatabaseNotFound;
use ActiveRedis\Exception\InvalidConfiguration;
use ActiveRedis\Exception\InvalidModelEncoding;
use ActiveRedis\Exception\RecordNotFound;
use ActiveRedis\Exception\TableNotFound;

/**
 *
 * Database
 *
 * Has many Tables. Dynamically loads them from config.
 *
 */
class Database implements Configurable
{
	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * @var string
	 */
	protected $keyPrefix = 'db:';

	/**
	 * @var Schema
	 */
	protected $schema;

	/**
	 * Configurable
	 */
	public function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			switch ($var) {

				// Set these by hand
				case 'connection':
				case 'schema':
					break;

				// Static config
				default:
					$this->{$var} = $val;
					break;
			}
		}

		$config = array_merge([
			'connection' => [],
			'schema' => [],
		], $config);

		$this->connection = $config['connection'] instanceof Connection
			? $config['connection']
			: new Connection($config['connection'])
		;

		$this->schema = $config['schema'] instanceof Schema
			? $config['schema']
			: new Schema($config['schema'])
		;
	}

	/**
	 * Forward calls to the connection
	 */
	public function __call($fn, $args)
	{
		return $this->query($fn, $args);
	}

	/**
	 * The connection is the actual connection to Redis-- the raw query mechanism
	 */
	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * Get the key prefix for this Database
	 */
	public function getKeyPrefix(): string
	{
		return $this->keyPrefix;
	}

	/**
	 * Get a table from the class name of a model
	 */
	public function getSchema(): Schema
	{
		return $this->schema;
	}

	/**
	 * Perform a query directly to the DB
	 */
	public function query(string $cmd, array $args = [])
	{
		return $this->getConnection()->__call($cmd, $args);
	}

}