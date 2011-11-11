<?php

namespace ActiveRedis;

abstract class Association 
{
	public $left;
	public $right;
	public $through;
	
	function __construct($leftClass, $rightClass, $options = null) 
	{	
		$this->left = $leftClass;
		$this->right = $rightClass;
		
		if (is_array($options)) {
			foreach ($options as $var => $val) {
				$this->$var = $val;
			}
		}
		
		// default "through"
		if (!$this->as) {
			$this->as = lcfirst(array_pop(explode("\\", $this->right)));
		}
	}
	
	function attach($table) {}
	function associate($left, $right) {}
}

abstract class HasManySorted extends HasMany {
	
	abstract function zscore($left);
	
	function associate($left, $right) {
		$left->db()->zadd($left->key($this->as), $this->zscore($left), $right->primaryKeyValue());
	}
}

class HasMany extends Association 
{
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function associatedModels($left) {
		$rightClass = $this->right;
		$left->db()->get($left->key($this->as));
	}
	
	function associate($left, $right) {
		$left->db()->sadd($left->get($this->as), $right->primaryKeyValue());
	}
	
	function beforeDelete($left) {
		$left->db()->srem($left->key($this->as), $left->primaryKeyValue());
	}
}

class BelongsTo extends Association 
{
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function beforeDelete($left) {
		$left->db()->del($left->key($this->as));
	}
}

?>