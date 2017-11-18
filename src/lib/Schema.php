<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Exception\InvalidConfiguration;
use ActiveRedis\Exception\TableNotFound;

/**
 * Database "schema"
 */
class Schema implements Configurable
{
	/**
	 * array of [$modelClass => $tableInstance]
	 */
	protected $tables = [];

	/**
	 * array of [$tableName => $modelClass]
	 */
	protected $tableNames = [];

	/**
	 * array of [$className => $genericInstance]
	 */
	protected $genericInstances = [];

	/**
	 * Configurable
	 */
	public function __construct(array $config = [])
	{
		$this->config = $this->normalizeConfig($config);

		// Hash table names
		foreach ($this->config['tables'] as $modelClass => $tableConf) {
			$this->tableNames[$tableConf['name']] = $modelClass;
		}
	}

	/**
	 * Get a table by model class
	 */
	public function getTable(string $modelClass): Table
	{
		if (!isset($this->tables[$modelClass])) {
			$this->tables[$modelClass] = $this->loadTable($modelClass);
		}
		return $this->tables[$modelClass];
	}

	/**
	 * Get table by name
	 * @throws TableNotFound
	 */
	public function getTableByName(string $tableName): Table
	{
		if (!isset($this->tableNames[$tableName])) {
			throw new TableNotFound('Table was not configured (name: ' . $tableName . ')');
		}
		return $this->getTable($this->tableNames[$tableName]);
	}

	/**
	 *
	 */
	protected function loadTable($className)
	{
		// Make sure this table exists
		if (!isset($this->config['tables'][$className])) {
			// $this->config['tables'][$className] = ['modelClass' => $className];
			throw new TableNotFound('Table not found: ' . $className);
		}

		// Get table config
		$tableConf = $this->config['tables'][$className];

		// Associations
		foreach ($tableConf['associations'] as $assocName => $assocConf) {
			$tableConf['associations'][$assocName] = $this->instantiateConfigurable(
				'', $assocConf, ['ActiveRedis\Association']
			);
		}

		// Behavior
		foreach ($tableConf['behaviors'] as $index => $behaviorConf) {
			$tableConf['behaviors'][$index] = $this->instantiateConfigurable(
				'', $behaviorConf, ['ActiveRedis\Behavior']
			);
		}

		// Create table
		return $this->instantiateConfigurable(Table::class, $tableConf);
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

		// If this is a generic instance (meaning no additional config), we can
		// and will reuse it for other tables. This helps a lot with large schemas.
		if (!$config) {
			if (!isset($this->genericInstances[$className])) {
				$this->genericInstances[$className] = new $className($config);
			}
			return $this->genericInstances[$className];
		}

		// Otherwise just instantiate normally
		return new $className($config);
	}

	/**
	 * Normalize a userland config into something with a more consistent shape
	 * @throws InvalidConfiguration
	 */
	protected function normalizeConfig(array $config): array
	{
		if (!isset($config['tables']) || !is_array($config['tables'])) {
			$config['tables'] = [];
		}

		if (!isset($config['defaultBehaviors']) || !is_array($config['defaultBehaviors'])) {
			$config['defaultBehaviors'] = [];
		}

		if (!isset($config['namespaces']) || !is_array($config['namespaces'])) {
			$config['namespaces'] = [];
		}

		$tables = [];

		foreach ($config['tables'] as $modelClass => $tableConf) {

			// Simple table string
			if (is_string($tableConf)) {
				$modelClass = $tableConf;
				$tableConf = [];
			}

			// Make sure config is an array
			if (!is_array($tableConf)) {
				$tableConf = [];
			}

			// Associations
			if (!isset($tableConf['associations'])) {
				$tableConf['associations'] = [];
			}
			foreach ($tableConf['associations'] as $assocName => $assocConf) {
				if (is_string($assocConf)) {
					$assocConf = array_combine(['class', 'rightClass'], explode(' ', $assocConf));
				}
				if ($assocConf instanceof AbstractAssociation) {
					continue;
				}
				if (!is_array($assocConf)) {
					throw new InvalidConfiguration('Association configuration must be either a string, array, or Association instance for table: ' . $modelClass);
				}
				if (!isset($assocConf['class'])) {
					throw new InvalidConfiguration('Missing explicit class for association on table: ' . $modelClass);
				}
				$assocConf['name'] = $assocName;
				$assocConf['leftClass'] = $modelClass;
				$tableConf['associations'][$assocName] = $assocConf;
			}

			// Behaviors
			$behaviors = [];

			// Default behavior
			if (!isset($tableConf['behaviors'])) {
				$tableConf['behaviors'] = $config['defaultBehaviors'] ?? [];
			}

			// Configured Behavior
			foreach ($tableConf['behaviors'] as $index => $behaviorConf) {
				if (is_string($behaviorConf)) {
					$behaviorConf = ['class' => $behaviorConf];
				}
				if (is_string($index) && !isset($behaviorConf['class'])) {
					$behaviorConf['class'] = ucfirst($index);
				}
				if (!isset($behaviorConf['class'])) {
					throw new InvalidConfiguration('Missing explicit class for behavior on table: ' . $modelClass);
				}
				$behaviors[] = $behaviorConf;
			}

			// Index Behavior
			// Very common type of behavior that is easily added
			if (is_array($tableConf['indexes'] ?? null)) {
				$behaviors[] = [
					'class' => 'Index',
					'attributes' => $tableConf['indexes']
				];
			}

			// Name
			if (!isset($tableConf['name'])) {
				$tableConf['name'] = $modelClass;
			}

			// Attach behaviors
			$tableConf['behaviors'] = $behaviors;

			// Make sure this table has the correct classname
			$tableConf['modelClass'] = $modelClass;

			// Assign new tableConf
			$tables[$modelClass] = $tableConf;
		}

		// Assign tables
		$config['tables'] = $tables;

		// Holla
		return $config;
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
}