<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Exception\AssociationNotFound;
use ActiveRedis\Exception\PreventDefault;

/**
 *
 * Table
 *
 */
class Table implements Configurable
{
	/**
	 * Array of association objects
	 * @var AbstractAssociation[]
	 */
	protected $associations = [];

	/**
	 * Array of Behavior objects
	 * @var AbstractBehavior[]
	 */
	protected $behaviors = [];

	/**
	 * @param Database
	 */
	protected $database;

	/**
	 * Array of Index objects
	 * @var Index[]
	 */
	protected $indexes = [];

	/**
	 * Model class name
	 * @var string
	 */
	protected $modelClass = '';

	/**
	 * Name of the table
	 * @var string
	 */
	protected $name = '';

	/**
	 * Configurable
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}

		// Tables must use their class
		$this->name = get_class($this);
	}

	/**
	 * Emit an event
	 * @param string $eventName
	 * @param array $args
	 * @return bool whether default was prevented
	 */
	public function emitEvent(string $eventName, array $args): bool
	{
		array_unshift($args, $this);
		foreach ($this->behaviors as $behavior) {
			if (method_exists($behavior, $eventName)) {
				try {
					$behavior->handleEvent($eventName, $args);
				} catch (PreventDefault $e) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Get the association
	 */
	public function getAssociation(string $name): AbstractAssociation
	{
		if (!isset($this->associations[$name])) {
			throw new AssociationNotFound('Association not found: ' . $this->getModelClass() . ' -> ' . $name);
		}
		return $this->associations[$name];
	}

	/**
	 * Get all associations
	 */
	public function getAssociations(): array
	{
		return $this->associations;
	}

	/**
	 * Get the model class of objects stored in this table
	 * @return string
	 */
	public function getModelClass(): string
	{
		return $this->modelClass;
	}

	/**
	 * Get the Database this Table belongs to
	 */
	public function getDatabase(): Database
	{
		return $this->database;
	}

	/**
	 * Get the name
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the key given some params
	 */
	public function getKey(array $params = [])
	{
		return $this->getDatabase()->getKey($this->getModelClass(), $params);
	}

	/**
	 * Read a model or models from the database
	 * @param Model $model
	 */
	public function read($primaryKey): Model
	{
		$this->emitEvent('beforeRead', [$primaryKey]);

		$model = $this->getDatabase()->getModel(
			$this->getKey($primaryKey)
		);

		$this->emitEvent('afterRead', [$model]);

		return $model;
	}

	/**
	 * Write the model to the database.
	 * @param Model $model
	 */
	public function write(Model $model): void
	{
		$this->emitEvent('beforeWrite', [$model]);

		// Write to the DB
		$this->getDatabase()->getConnection()->set(
			$model->getDbKey(),
			$this->getDatabase()->encodeModel($model)
		);

		$this->emitEvent('afterWrite', [$model]);
	}

}







