<?php

declare(strict_types=1);

namespace ActiveRedis\Table;

use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Configurable;
use ActiveRedis\Database;
use ActiveRedis\Exception\AssociationNotFound;
use ActiveRedis\Exception\PreventDefault;
use ActiveRedis\Exception\QueryNotSupported;
use ActiveRedis\Exception\RecordNotFound;
use ActiveRedis\Model;
use ActiveRedis\Query;
use ActiveRedis\QueryResult;

/**
 *
 * Table
 *
 */
class BasicTable implements TableInterface, Configurable
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
	 * @var array
	 */
	protected $primaryKey = ['id'];

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
	 * Decode data stored in the database
	 */
	protected function decodeData(string $data): array
	{
		return json_decode($data, true) ?: [];
		// TODO: error handling
	}

	/**
	 * Decode a model stored in the database
	 */
	protected function decodeModel(string $data): Model
	{
		$attr = $this->decodeData($data, true);
		$modelClass = $this->getModelClass();
		return new $modelClass([
			'attributes' => $attr,
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
	 * Encode arbitrary data for storage in the database
	 */
	protected function encodeData(array $data): string
	{
		return json_encode($data);
		// TODO: error handling
	}

	/**
	 * Encode a model for storage in the database
	 */
	protected function encodeModel(Model $model): string
	{
		return $this->encodeData($model->getAttributes());
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
	 * Get a model from the table
	 */
	public function getModel(array $primaryKey): Model
	{
		$key = $this->getKey($primaryKey);
		$data = $this->getDatabase()->get($key);
		if (!$data) {
			throw new RecordNotFound('Could not find record with key: ' . $key);
		}
		return $this->decodeModel($data);
	}

	/**
	 * Model references are stored against indexes
	 */
	public function getModelByRef(string $ref): Model
	{
		$primaryKey = $this->getModel($this->decodeData($ref));
	}

	/**
	 * Model references are stored against indexes
	 */
	public function getModelRef(Model $model): string
	{
		return $this->encodeData($model->getPrimaryKey());
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
	 * Returns an array of primary key attribute names
	 */
	public function getPrimaryKey(): array
	{
		return $this->primaryKey;
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
	 * Run a query against the table
	 * @param Query $query
	 * @return QueryResult
	 */
	public function runQuery(Query $query): QueryResult
	{
		// Conditions
		$where = $query->getWhere();
		if (!$where) {
			throw new QueryNotSupported('Operations without a where clause is not yet supported on ' . get_class($this));
		}

		// Redis key
		$key = $this->getKey($where);

		// Determine whether this is a primary key or not
		$isPrimaryKey = ! array_diff_key(
			array_flip($this->getPrimaryKey()),
			$where
		);

		// Select
		if ($query->isSelect()) {
			if ($isPrimaryKey) {
				$data = $this->getDatabase()->get($key);
				$attr = $this->decodeData($data);
				$modelClass = $this->getModelClass();
				return new QueryResult([
					'iterator' => new ArrayIterator([
						$this->getModel($where),
					]),
				]);
			} else {
				return new Queryresult([
					'iterator' =>
				]);
			}
		}

		// Insert or Update
		if ($query->isInsert() || $query->isUpdate()) {
			// TODO: do we get the whole model here? just some attributes?
			// TODO: do we change encodeModel and decodeModel to encodeData and decodeData?
		}

		// TODO: Delete
	}

	/**
	 * Save a model to the database.
	 */
	public function saveModel(Model $model): void
	{
		$this->getDatabase()->set(
			$this->getKey($model->getPrimaryKey()),
			$this->encodeModel($model)
		);
	}
}
