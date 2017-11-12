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
	 * Key separator
	 * @var string
	 */
	protected $keySeparator = ':';

	/**
	 * Key prefix
	 * @var string
	 */
	protected $keyPrefix = 'table';

	/**
	 * Configurable
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}
	}

	public function attributesEncode(array $attributes): string
	{
		return json_encode($attributes);
	}

	public function attributesDecode(string $encodedAttributes): array
	{
		return json_decode($encodedAttributes, true);
	}

	/**
	 * Emit an event
	 * @param string $eventName
	 * @param array $args
	 * @return bool whether default was prevented
	 */
	public function emitEvent(string $eventName, array $args): bool
	{
		foreach ($this->behaviors as $behavior) {
			if (method_exists($behavior, $eventName)) {
				try {
					call_user_func_array([$behavior, $eventName], $args);
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
	 * Get the model class of objects stored in this table
	 * @return string
	 */
	public function getModelClass(): string
	{
		return $this->modelClass;
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
	public function getKey(array $params)
	{
		return $this->keyPrefix . $this->keySeparator . $this->name . '?' . http_build_query($params);
	}

	/**
	 * Read a model or models from the database
	 * @param Model $model
	 */
	public function read($primaryKey): Model
	{
		$this->emitEvent('beforeWrite', [$model]);

		// Read from the DB
		$modelClass = $this->getModelClass();
		$json = $this->db()->get($this->getKey($primaryKey));
		$model = new $modelClass($this->decode($json));

		$this->emitEvent('afterWrite', [$model]);

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
		$this->db()->set(
			$this->getKey($model->getPrimaryKey()),
			$this->encode($model->getAttributes())
		);

		$this->emitEvent('afterWrite', [$model]);
	}

}







