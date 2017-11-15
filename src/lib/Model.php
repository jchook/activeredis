<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Exception\AssociationNotFound;

abstract class Model implements Configurable
{
	/**
	 * @var string
	 */
	protected static $db = 'default';

	/**
	 * @var array
	 */
	protected static $primaryKey = ['id'];

	/**
	 * @var array of [string => mixed]
	 */
	protected $attributes = [];

	/**
	 * @var array of [string => Model|Model[]]
	 */
	protected $associated = [];

	/**
	 * Holds original values of
	 * @var array of [string => mixed]
	 */
	protected $changed = [];

	/**
	 * Get the Database to which this Model's Table belongs
	 * @return Database
	 */
	public static function db(): Database
	{
		return Provider::getDatabase(static::$db);
	}

	/**
	 * Get the Table to which this Model belongs
	 * @return Table
	 */
	public static function table(): Table
	{
		return static::db()->getTable(static::class);
	}

	/**
	 * Find objects by their attributes
	 */
	public static function findBy(array $params): array
	{
		// TODO: clean this up. It doesn't belong here.
		$db = static::db();
		$raw = $db->getConnection();
		$args = [];
		foreach ($params as $param) {
			$args[] = $this->getKey((array)$param);
		}
		$inter = call_user_func_array([$raw, 'sinter'], $args);
		if (!$inter || !is_array($inter)) {
			return [];
		}
		return function() use ($inter, $table) {
			foreach ($inter as $dbKey) {
				yield $db->getModel($dbKey);
			}
		};
	}

	/**
	 * Create an instance of this model
	 *
	 * @param mixed $id primary key or array of property => value pairs
	 * @param bool $load whether to load the record by ID from the DB
	 */
	function __construct(array $config = [])
	{
		$this::table()->emitEvent('beforeConstruct', [$this]);
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}
		$this::table()->emitEvent('afterConstruct', [$this]);
	}

	/**
	 * Dynamic get attribute / association
	 */
	function __get($var)
	{
		// Dynamic Initialization
		$method = 'get' . ucfirst($var);
		if (method_exists($this, $method)) {
			return $this->$method();
		}

		// Attributes
		if (array_key_exists($var, $this->attributes)) {
			return $this->attributes[$var];
		}

		// Association
		try {
			if (!isset($this->associated[$var])) {
				$association = $this::table()->getAssociation($var);
				$this->associated[$var] = $association->getAssociated($this);
				return $this->associated[$var];
			}
		} catch (AssociationNotFound $e) {}

		// Notice
		trigger_error(
			'Undefined property: ' . get_class($this), '->' . $var .
			' on line ' . __LINE__ . ' in file ' . __FILE__,
			E_USER_NOTICE
		);
	}

	/**
	 * Support isset for attributes / associations
	 */
	function __isset($var)
	{
		return isset($this->attributes[$var]) || isset($this->associated[$var]);
	}

	/**
	 * Dynamic set attribute / association
	 */
	function __set($var, $val)
	{
		// Dynamic Initialization
		$method = 'set' . ucfirst($var);
		if (method_exists($this, $method)) {
			return $this->$method($val);
		}

		try {
			$assoc = $this::table()->getAssociation($var);
			return $assoc->associate($this, $var);
		} catch (AssociationNotFound $e) {}

		// Attribute
		$this->setAttribute($var, $val);
	}

	/**
	 * Support unset for attributes / associations
	 */
	function __unset($var)
	{
		if (isset($this->attributes[$var])) {
			unset($this->attributes[$var]);
		} elseif (isset($this->associated[$var])) {
			unset($this->associated[$var]);
		}
	}

	/**
	 * Gets or sets an attribute by name
	 *
	 * @param string $get
	 * @param mixed $set optional
	 * @return mixed
	 */
	public function attr($get, $set = null)
	{
		if (!is_null($set)) {
			$this->setAttribute($get, $set);
		}
		return $this->getAttribute($get);
	}

	/**
	 * Emits an event to the Table
	 */
	public function emitEvent(string $eventName, array $args = []): void
	{
		$this::table()->emitEvent($eventName, $args);
	}

	/**
	 * Returns true if the model is "dirty", meaning there are changes since the
	 * last save.
	 * @return bool
	 */
	public function hasChanged(): bool
	{
		return (bool) $this->changed;
	}

	/**
	 * Delta represents the changes in attributes
	 */
	public function getChanged(): array
	{
		return $this->changed;
	}

	/**
	 * Get a single attribute by name
	 * @param string $var
	 * @return mixed null if the attribute does not exist
	 */
	public function getAttribute(string $name)
	{
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		trigger_error('Undefined attribute ' . $name . ' in ' . get_class($this));
	}

	/**
	 * Get all attributes or a subset of attributes
	 * @param array|null $keys
	 * @return array
	 */
	public function getAttributes(?array $keys = null): array
	{
		if (is_null($keys)) {
			return $this->attributes;
		}
		return array_intersect_key($this->attributes, array_flip($keys));
	}

	/**
	 * Get the Database to which this model's Table belongs
	 */
	public function getDb(): Database
	{
		return Provider::getDatabase($this::$db);
	}

	/**
	 * Get a unique string key against which this model can be stored in Redis
	 * @return string
	 */
	public function getDbKey(): string
	{
		return $this::table()->getKey($this->getPrimaryKey());
	}

	/**
	 * Get the key => val of this model's primary key
	 * @return array
	 */
	public function getPrimaryKey(): array
	{
		return $this->getAttributes($this::$primaryKey);
	}

	/**
	 * Get the table to which this model belongs
	 */
	public function getTable(): Table
	{
		return $this->getDb()->getTable(get_class($this));
	}

	/**
	 * True if the attribute named exists
	 * @param string $var name
	 * @return bool
	 */
	public function hasAttribute(string $name): bool
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * Save this model and any changes to the DB
	 */
	public function save(): void
	{
		$this::table()->write($this);
		$this->changed = [];
	}

	/**
	 * Set a single attribute value by name
	 * @param string $var
	 * @param mixed $val
	 * @param bool $ignore
	 * @return mixed $val
	 */
	public function setAttribute(string $name, $value, $asChange = true): void
	{
		if ((($this->attributes[$name] ?? null) !== $value) || isset($this->changed[$name])) {
			$this->attributes[$name] = $value;
			if ($asChange) {
				$this->changed[$name] = array_key_exists($name, $this->changed)
					? $this->changed[$name]
					: ($this->attributes[$name] ?? null)
				;
			}
		}
	}

	/**
	 * Set attributes via name => $value pairs
	 * @param array $attributes
	 */
	public function setAttributes($attributes, $asDelta = true): void
	{
		foreach ($attributes as $var => $val) {
			$this->setAttribute($var, $val, $asDelta);
		}
	}
}