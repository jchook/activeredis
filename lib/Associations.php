<?php

namespace ActiveRedis;

abstract class Association 
{
	public static $poly = false;
	
	public $left;
	
	public $leftClass;
	public $rightClass;
	
	public $name;
	
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
		
		// default
		if (!$this->name) {
			$this->name = lcfirst(array_pop(explode("\\", $this->rightClass)));
		}
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
	function &delegate($left) {
		$this->left = $left;
		return $this;
	}
	
	
	function get() 
	{
		// Allow the result of the callbacks to override the model
		// This lends a lot of power to Behaviors
		$result = $this->left->table()->trigger('beforeGetAssociated', array($this->left, $this));
		if (!is_null($result)) {
			return $result;
		}
		
		// Okay, let the particular association have a word
		// be flexible about the arguments too
		$args = func_get_args();
		array_unshift($args, $this->left);
		$result = call_user_func_array(array($this, 'associated'), $args);
		
		// But also let the afterGetAssociated function totally override the result
		if (null !== ($altResult = $this->left->table()->trigger('afterGetAssociated', array($this->left, $this, $result)))) {
			return $altResult;
		}
		
		// Hopefully this is a properly associated model object
		return $result;
	}
	
	function put($right) {
		$this->left->table()->trigger('beforePutAssociated', array($this->left, $this, $right));
		$result = $this->associate($this->left, $right);
		$this->left->table()->trigger('afterPutAssociated', array($this->left, $this, $right));
		return $result;
	}
	
	
}

class HasOne extends Association
{
	function associate(Model $left, Model $right) {
		$left->db()->set($left->key($this->name), $right->primaryKeyValue());
	}
	
	function set(Model $right) {
		$this->namesociate($this->left, $right);
	}
	
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function beforeDelete($left) {
		$left->db()->del($left->key($this->name));
	}
	
	function associated(Model $left) {
		// get a model
		$rightClass = $this->rightClass;
		if ($id = $left->table()->get($this->name)) {
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
		return $this->namesociated($this->left, $start, $length);
	}
	
	function add(Model $right) {
		$this->namesociate($this->left, $right);
	}
	
	function associate(Model $left, Model $right) {
		$left->db()->sadd($left->key($this->name), $right->primaryKeyValue());
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

class HasManySorted extends HasMany {
	
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