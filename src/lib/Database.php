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
	const CLASS_ATTRIBUTE = '__';

	/**
	 * Store the config array for dynamically loaded components like Tables
	 * @var array
	 */
	protected $config = [];

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Default table behaviors
	 */
	protected $defaultBehavior = ['Identify'];

	/**
	 * Key separator
	 * @var string
	 */
	protected $keySeparator = ':';

	/**
	 * Key prefix
	 * @var string
	 */
	protected $keyPrefix = 'db';

	/**
	 * @var Table[]
	 */
	protected $tables = [];

	/**
	 * Configurable
	 */
	public function __construct(array $config = [])
	{
		$this->config = $config;
		foreach ($config as $var => $val) {
			switch ($var) {

				// Connection can be set directly IFF it's a Connection. Otherwise the
				// config is interpreted dynamically.
				case 'connection':
					if ($val instanceof Connection) {
						$this->connection = $val;
					}
					break;

				// Tables are dynamically loaded
				case 'tables':
					break;

				// Static config
				default:
					$this->{$var} = $val;
					break;
			}
		}
	}

	/**
	 * Decode a model stored in the database
	 */
	public function decodeModel(string $data): Model
	{
		list($modelClass, $attr) = explode($this->keySeparator, $data);
		$attr = json_decode($data, true);
		$modelClass = $attr[self::CLASS_ATTRIBUTE];
		unset($attr[self::CLASS_ATTRIBUTE]);
		if (!$modelClass || !class_exists($modelClass)) {
			throw new InvalidModelEncoding('Could not find class for encoded model data: ' . $data);
		}
		return new $modelClass($attr);
	}

	/**
	 * Encode a model for storage in the database
	 */
	public function encodeModel(Model $model): string
	{
		return json_encode(array_merge(
			[self::CLASS_ATTRIBUTE => get_class($model)],
			$model->getAttributes()
		));
	}

	/**
	 * Fetch a model by DB key
	 */
	public function getModel(string $dbKey): Model
	{
		$data = $this->getConnection()->get($dbKey);
		if (!$data) {
			throw new RecordNotFound('Could not find record with key: ' . $dbKey);
		}
		return $this->decodeModel($data);
	}

	/**
	 * The connection is the actual connection to Redis-- the raw query mechanism
	 */
	public function getConnection(): Connection
	{
		if (!$this->connection) {
			$config = $this->config['connection'] ?? [];
			$this->connection = $this->_instantiateConfigurable(Connection::class, $config);
		}
		return $this->connection;
	}

	/**
	 * Get the key prefix for this Database
	 */
	public function getKeyPrefix(): string
	{
		return $this->keyPrefix();
	}

	/**
	 * Get the key for a table and a set of attributes
	 */
	public function getKey($tableName, array $params): string
	{
		return $this->keyPrefix . $this->keySeparator . $tableName . '?' . http_build_query($params);
	}

	/**
	 * Get a table from the class name of a model
	 */
	public function getTable(string $className): Table
	{
		if (!isset($this->tables[$className])) {
			if (!$this->_loadTable($className)) {
				$this->tables[$className] = new Table([
					'database' => $this,
					'modelClass' => $className,
				]);
			}
		}
		return $this->tables[$className];
	}

	/**
	 * Set a model in the database. Note that you should not use this directly.
	 * @see Model::save()
	 */
	public function setModel(Model $model): void
	{
		$table = $this->getTable(get_class($model));
		$this->getConnection()->set(
			$this->getKey($table->getName(), $model->getPrimaryKey()),
			$this->encodeModel($model)
		);
	}

	/**
	 * Instantiate a configured object
	 */
	protected function _instantiateConfigurable(string $defaultClass, array $config, array $namespaces = []): Configurable
	{
		$className = $defaultClass;
		if (isset($config['class'])) {
			$className = $this->_resolveClassName($config['class'], array_merge($this->config['namespaces'] ?? [], $namespaces));
			unset($config['class']);
		}
		return new $className($config);
	}

	/**
	 * Dynamically load a table from config
	 */
	protected function _loadTable(string $className): bool
	{
		if (!isset($this->config['tables'][$className])) {
			return false;
		}

		// Get table config
		$config = $this->config['tables'][$className];

		// Make sure config is an array
		if (!is_array($config)) {
			throw new InvalidConfiguration('Table configuration must be a string or a Table for: ' . $className);
		}

		// Associations
		if (isset($config['associations'])) {
			foreach ($config['associations'] as $assocName => $assocConf) {
				if (is_string($assocConf)) {
					$assocConf = array_combine(['class', 'rightClass'], explode(' ', $assocConf));
				}
				if ($assocConf instanceof AbstractAssociation) {
					$config['associations'][$assocName] = $assocConf;
					continue;
				}
				if (!is_array($assocConf)) {
					throw new InvalidConfiguration('Association configuration must be either a string, array, or Association instance for table: ' . $className);
				}
				if (!isset($assocConf['class'])) {
					throw new InvalidConfiguration('Missing explicit class for association on table: ' . $className);
				}
				$assocConf['name'] = $assocName;
				$assocConf['leftClass'] = $className;
				$config['associations'][$assocName] = $this->_instantiateConfigurable(
					'', $assocConf, ['ActiveRedis\Association']
				);
			}
		}

		// Behaviors
		$behaviors = [];

		// Default behavior
		if (!isset($config['behaviors'])) {
			$config['behaviors'] = $this->defaultBehaviors ?? [];
		}

		// Configured Behavior
		foreach ($config['behaviors'] as $index => $behaviorConf) {
			if (is_string($behaviorConf)) {
				$behaviorConf = ['class' => $behaviorConf];
			}
			if ($behaviorConf instanceof AbstractBehavior) {
				$behaviors[] = $behaviorConf;
				continue;
			}
			if (is_string($index) && !isset($behaviorConf['class'])) {
				$behaviorConf['class'] = ucfirst($index);
			}
			if (!isset($behaviorConf['class'])) {
				throw new InvalidConfiguration('Missing explicit class for behavior on table: ' . $className);
			}
			$behaviors[] = $this->_instantiateConfigurable(
				'', $behaviorConf, ['ActiveRedis\Behavior']
			);
		}

		// Index Behavior
		// Very common type of behavior that should be easily added
		if (is_array($config['indexes'] ?? null)) {
			$behaviors[] = new Index(['attributes' => $config['indexes']]);
		}

		// Attach behaviors
		$config['behaviors'] = $behaviors;

		// Attach database
		$config['database'] = $this;

		// Make sure this table has the correct classname
		$config['modelClass'] = $className;

		// Create table
		$this->tables[$className] = $this->_instantiateConfigurable(Table::class, $config);
		return true;
	}

	/**
	 * Resolve a relative class name
	 */
	protected function _resolveClassName(string $rawInput, array $namespaces = []): string
	{
		$className = '\\' . ltrim($rawInput, '\\');
		if (class_exists($className)) {
			return $className;
		}
		foreach ($namespaces as $namespace) {
			$otherClassName = $namespace . $className;
			if (class_exists($otherClassName)) {
				return $otherClassName;
			}
		}
		throw new ClassNotFound('Could not resolve class: ' . $rawInput);
	}
}