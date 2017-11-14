<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Behavior\AbstractBehavior;
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
	const CLASS_ATTRIBUTE = '__class';

	protected $connection;
	protected $config = [];

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

	public function __construct(array $config = [])
	{
		$this->config = $config;
	}

	public function decodeModel(string $data): Model
	{
		list($modelClass, $attr) = explode($this->keySeparator, $data);
		$attr = json_decode($data, true);
		$modelClass = $attr[self::CLASS_ATTRIBUTE];
		unset($attr[self::CLASS_ATTRIBUTE]);
		if (!$modelClass || !class_exists($modelClass)) {
			print_r($attr);
			throw new InvalidModelEncoding('Could not find class for encoded model data: ' . $data);
		}
		return new $modelClass($attr);
	}

	public function encodeModel(Model $model): string
	{
		return json_encode(array_merge(
			[self::CLASS_ATTRIBUTE => get_class($model)],
			$model->getAttributes()
		));
	}

	public function getModel(string $dbKey): Model
	{
		$data = $this->getConnection()->get($dbKey);
		if (!$data) {
			throw new RecordNotFound('Could not find record with key: ' . $dbKey);
		}
		return $this->decodeModel($data);
	}

	public function getConnection(): Connection
	{
		if (!$this->connection) {
			$config = $this->config['connection'] ?? [];
			$this->connection = $this->instantiateConfigurable(Connection::class, $config);
		}
		return $this->connection;
	}

	public function getKey($tableName, array $params)
	{
		return $this->keyPrefix . $this->keySeparator . $tableName . '?' . http_build_query($params);
	}

	/**
	 * Get a table from the class name of a model
	 */
	public function getTable(string $className): Table
	{
		if (!isset($this->tables[$className])) {
			if (!$this->loadTable($className)) {
				throw new TableNotFound('Table not found for model class: ' . $className);
			}
		}
		return $this->tables[$className];
	}

	/**
	 * Instantiate a configured object
	 */
	protected function instantiateConfigurable(string $defaultClass, array $config, array $namespaces = []): Configurable
	{
		$className = $defaultClass;
		if (isset($config['class'])) {
			$className = $this->resolveClassName($config['class'], array_merge($this->config['namespaces'] ?? [], $namespaces));
			unset($config['class']);
		}
		return new $className($config);
	}

	/**
	 * Resolve a relative class name
	 */
	protected function resolveClassName(string $rawInput, array $namespaces = []): string
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

	/**
	 * Dynamically load a table from config
	 */
	protected function loadTable(string $className): bool
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

		// Make sure this table has a model class
		if (!isset($config['modelClass'])) {
			$config['modelClass'] = $className;
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
				$config['associations'][$assocName] = $this->instantiateConfigurable(
					'', $assocConf, ['ActiveRedis\Association']
				);
			}
		}

		// Behavior
		if (isset($config['behaviors'])) {
			foreach ($config['behaviors'] as $index => $behaviorConf) {
				if (is_string($behaviorConf)) {
					$behaviorConf = ['class' => $behaviorConf];
				}
				if ($behaviorConf instanceof AbstractBehavior) {
					$config['behavior'][$index] = $behaviorConf;
					continue;
				}
				if (!isset($behaviorConf['class'])) {
					throw new InvalidConfiguration('Missing explicit class for behavior on table: ' . $className);
				}
				$config['behavior'][$index] = $this->instantiateConfigurable(
					'', $behaviorConf, ['ActiveRedis\Behavior']
				);
			}
		}

		// Attach database
		$config['database'] = $this;

		// Create table
		$this->tables[$className] = $this->instantiateConfigurable(Table::class, $config);
		return true;
	}
}