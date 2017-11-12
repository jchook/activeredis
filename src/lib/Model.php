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
	 * @var array of [string => bool]
	 */
	protected $isDirty = [];

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
		static::db()->table(static::class);
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

		// Association
		elseif ($association = $this::table()->association($var)) {
			$this->isDirty[$var] = true;
			return $this->associated[$var] = $val;
		}

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
			return $this->setAttribute($get, $set);
		}
		return $this->getAttribute($get);
	}

	/**
	 * Returns true if the model is "dirty", meaning there are changes since the
	 * last save.
	 * @return bool
	 */
	public function isDirty(): bool
	{
		return (bool) $this->isDirty;
	}

	/**
	 * Get a single attribute by name
	 * @param string $var
	 * @return mixed null if the attribute does not exist
	 */
	function getAttribute(string $name)
	{
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		trigger_error('Undefined attribute ' . $name . ' in ' . get_class($this));
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
	 * True if the attribute named exists
	 * @param string $var name
	 * @return bool
	 */
	function hasAttribute(string $name): bool
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * Save this model and any changes to the DB
	 */
	public function save()
	{
		$this::table()->write($this);
	}

	/**
	 * Set a single attribute value by name
	 * @param string $var
	 * @param mixed $val
	 * @param bool $makeDirty
	 * @return mixed $val
	 */
	public function setAttribute(string $name, $value, bool $makeDirty = true): void
	{
		if ($makeDirty && (!isset($this->attributes[$name]) || ($this->attributes[$name] !== $value))) {
			$this->isDirty[$name] = true;
		}
		$this->attributes[$name] = $value;
	}

	/**
	 * Set attributes via name => $value pairs
	 * @param array $attributes
	 * @return null
	 */
	public function setAttributes($attributes, $makeDirty = true)
	{
		foreach ($attributes as $var => $val) {
			$this->setAttribute($var, $val, $makeDirty);
		}
	}
}