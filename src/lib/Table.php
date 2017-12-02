<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Association\AbstractAssociation;
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

		// This is a required variable
		if (!$this->modelClass) {
			throw new \Exception('Invalid model class: ' . $this->modelClass);
		}

		// By default name the table after the class of things it holds
		// This can be shortened to save on storage space or key length
		if (!$this->name) {
			$this->name = $this->modelClass;
		}
	}

	/**
	 * Decode a model stored in the database
	 */
	protected function decodeModel(string $data): Model
	{
		$attr = json_decode($data, true);
		$modelClass = $this->getModelClass();
		return new $modelClass([
			'attributes' => $attr
		]);
	}

	/**
	 * Emit an event
	 * @param string $eventName
	 * @param array $args
	 * @return bool returns false if default is prevented
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
	 * Encode a model for storage in the database
	 */
	protected function encodeModel(Model $model): string
	{
		return json_encode($model->getAttributes());
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
	 * Get the Database this Table belongs to
	 */
	public function getDatabase(): Database
	{
		if (!$this->database) {
			$modelClass = $this->getModelClass();
			$this->database = $modelClass::db();
		}
		return $this->database;
	}

	/**
	 * Get the key given some params
	 */
	public function getKey(array $params = [])
	{
		return $this->getDatabase()->getKeyPrefix() . $this->getName() . '?' . http_build_query($params);
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
	 * Fetch a model by DB key
	 */
	public function getModel(string $dbKey): Model
	{
		$data = $this->getDatabase()->get($dbKey);
		if (!$data) {
			throw new RecordNotFound('Could not find record with key: ' . $dbKey);
		}
		return $this->decodeModel($data);
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
	 * Whether the table has the named association.
	 * @param string $name
	 * @return bool
	 */
	public function hasAssociation(string $name): bool
	{
		return isset($this->associations[$name]);
	}

	/**
	 * Save a model to the database.
	 * @see Model::save()
	 */
	public function saveModel(Model $model): void
	{
		$this->getDatabase()->set(
			$this->getKey($model->getPrimaryKey()),
			$this->encodeModel($model)
		);
	}

}
