<?php

namespace ActiveRedis;

abstract class Association 
{
	public static $poly = false;
	
	public $left;
	
	public $leftClass;
	public $rightClass;
	
	public $name;
	public $foreignKey;
	
	public $eager; // whether to autolaod on default
	
	function __construct($leftClass, $rightClass, $options = null) 
	{	
		$this->leftClass = $leftClass;
		$this->rightClass = $rightClass;
		
		if (is_array($options)) {
			foreach ($options as $var => $val) {
				$this->$var = $val;
			}
		}
		
		// Default name
		$this->name or $this->name = lcfirst(array_pop(explode('\\', get_class($this))));
		
		// Default foreign key
		$this->foreignKey or $this->foreignKey = $this->name . '_' . $rightClass::primaryKey();
	}
	
	/**
	 * Attach this association to a table
	 */
	function attach(Table $table) {}
	
	/**
	 * Associate a left-side model with a right-side model
	 */
	function associate(Model $left, Model $right) {}
	
	/**
	 * Get the "right-side" objects associated with the "left-side"
	 */
	function associated(Model $left) {}
	
	/**
	 * Called whenever a "left-side" model is constructed
	 */
	function autoload(Model $left) 
	{
		return $this->eager ? $this->associated($left) : null;
	}
	
	/**
	 * Returned by the left model when a user accesses the association
	 */
	function &delegate($left = null) {
		if (!$left && !$this->left) {
			throw new Exception('Cannot produce associated delegate for ' . $this->leftClass . '->' . $this->name . ' because no model object was given.');
		}
		$this->left = $left;
		return $this;
	}
}

class HasOne extends Association
{
	function associate(Model $left, Model $right) {
		$left->setAttribute($this->foreignKey, $right->primaryKeyValue());
	}
	
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function beforeDelete($left) {
		$left->setAttribute($this->foreignKey, null);
	}
	
	function associated(Model $left) {
		// get a model
		$rightClass = $this->rightClass;
		if ($id = $left->table()->get($this->foreignKey)) {
			return $rightClass::find($id);
		}
	}
}

class BelongsTo extends HasOne 
{
	// Does the same thing as HasOne
}

class HasMany extends Association 
{
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function associate(Model $left, Model $right) {
		$left->setAttribute($this->rightForeignKey, $right->primaryKeyValue());
	}
	
	function dataAssociatedWith(Model $left) {
		return $left->db()->sGetMembers($left->key($this->name));
	}
	
	function associated(Model $left, $start = null, $length = null) {
		// get some objects
	}
	
	function beforeDelete(Model $left) {
		$left->db()->srem($left->key($this->name), $left->primaryKeyValue());
	}
}

class HasManySorted extends HasMany 
{	
	public $by;
	
	function attach(Table $table) {}
	
	function zscore($left) {
		return $left->getAttribute($this->by);
	}
	
	function associated(Model $left) {
		
	}
	
	function associate(Model $left, Model $right) {
		$left->db()->zadd($left->key($this->name), $this->zscore($left), $right->primaryKeyValue());
	}
}

?>