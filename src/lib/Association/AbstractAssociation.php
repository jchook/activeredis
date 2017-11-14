<?php

namespace ActiveRedis\Association;
use ActiveRedis\Configurable;
use ActiveRedis\Inflector;

/**
 * Association
 *
 * Associations allow you to define meaningful relationships between
 * models classes. For example, 'HasOne User', or 'HasMany Order'.
 *
 * You can define your own types of associations easily. Simply extend
 * and implement the ActiveRedis\Association\AbstractAssociation class.
 *
 * @see ActiveRedis\Association\HasOne
 * @see ActiveRedis\Association\HasMany
 * @see ActiveRedis\Association\BelongsTo
 */
abstract class AbstractAssociation implements Configurable
{
	public static $poly = false;

	public $left;

	public $leftClass;
	public $rightClass;

	public $name;
	public $foreignKey;

	public $eager; // whether to autolaod on default

	function __construct(array $config = [])
	{
		// Extract config to this association object
		if (is_array($config)) {
			foreach ($config as $var => $val) {
				$this->$var = $val;
			}
		}

		// Default name
		if (!$this->name) {
			$pieces = explode('\\', $this->rightClass);
			$this->name = lcfirst(array_pop($pieces));
			if ($this::$poly) {
				$this->name = Inflector::pluralize($this->name);
			}
		}

		// Default foreign key
		if (!$this->foreignKey) {
			$this->foreignKey = $this->name . '_id';
		}
	}

	/**
	 * Associate a left-side model with a right-side model
	 *
	 * @param Model $left
	 * @param Model $right
	 * @return null
	 */
	public function associate(Model $left, Model $right) {}

	/**
	 * Associate a left-side model with a right-side model
	 *
	 * @param Model $left
	 * @return Model|iterable
	 */
	public function getAssocated(Model $left) {}
}
