<?php

namespace ActiveRedis;

abstract class Association 
{
	public $left;
	
	protected $leftClass;
	protected $rightClass;
	protected $as;
	
	function __construct($leftClass, $rightClass, $options = null) 
	{	
		$this->leftClass = $leftClass;
		$this->rightClass = $rightClass;
		
		if (is_array($options)) {
			foreach ($options as $var => $val) {
				$this->$var = $val;
			}
		}
		
		// default "through"
		if (!$this->as) {
			$this->as = lcfirst(array_pop(explode("\\", $this->rightClass)));
		}
	}
	
	function attach(Table $table) {}
	abstract function associate(Model $left, Model $right);
	abstract function associated(Model $left);
	
	/**
	 * Called by the left model when a user accesses the association
	 */
	function &delegate($left) {
		$this->left = $left;
		return $this;
	}
}

class HasOne extends Association
{
	function associate(Model $left, Model $right) {
		$left->db()->set($left->key($this->as), $right->primaryKeyValue());
	}
	
	function set(Model $right) {
		$this->associate($this->left, $right);
	}
	
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function beforeDelete($left) {
		$left->db()->del($left->key($this->as));
	}
	
	function associated(Model $left) {
		// get a model
		$rightClass = $this->rightClass;
		if ($id = $left->table()->get($this->as)) {
			return $rightClass::find($id);
		}
	}
}

class BelongsTo extends HasOne {}

class HasMany extends Association 
{
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function get($start = null, $length = null) {
		return $this->associated($this->left, $start, $length);
	}
	
	function add(Model $right) {
		$this->associate($this->left, $right);
	}
	
	function associate(Model $left, Model $right) {
		$left->db()->sadd($left->key($this->as), $right->primaryKeyValue());
	}
	
	function dataAssociatedWith(Model $left) {
		return $left->db()->sGetMembers($left->key($this->as));
	}
	
	function associated(Model $left, $start = null, $length = null) {
		// get some objects
	}
	
	function beforeDelete(Model $left) {
		$left->db()->srem($left->key($this->as), $left->primaryKeyValue());
	}
}

class HasManySorted extends HasMany {
	
	public $by;
	
	function attach(Table $table) {}
	
	function zscore($left) {
		return $left->getAttribute($this->by);
	}
	
	function associated(Model $left) {
		
	}
	
	function associate(Model $left, Model $right) {
		$left->db()->zadd($left->key($this->as), $this->zscore($left), $right->primaryKeyValue());
	}
}

?>