<?php

namespace ActiveRedis;

/**
 * Association
 * 
 * Associations allow you to define meaningful relationships between
 * models classes. For example, 'HasOne User', or 'HasMany Orders'.
 * 
 * You can define your own types of associations easily. Simply extend
 * and implement the ActiveRedis\Association class.
 * 
 * Defining associations is easy. In your model class, simply add...
 * 	static $associations = array('AssociationClass ForeignModelClass', ...);
 * 
 * Replace AssociationClass with the appropriate class name, such as HasMany,
 * and replace ForeignModelClass with the actual foreign model class name.
 * When AssociationClass::$poly == true, ForeignModelClass is pluralized.
 * 
 * If your association requires configuration, make the association 
 * statement the first element of the configuration array, such as:
 * 	static $associations = array(array('HasMany Users', 'name' => 'owner', ...), ...);
 * 
 * @see HasOne
 * @see HasMany
 * @see BelongsTo
 */
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
		
		// Extract options to this association object
		if (is_array($options)) {
			foreach ($options as $var => $val) {
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
			if ($this::$poly) {
				$this->foreignKey = Inflector::singularize($this->name) . '_ids';
			} else {
				$this->foreignKey = $this->name . '_id';
			}
		}
	}
	
	/**
	 * Attach this association to a table
	 * 
	 * @param Table $table
	 * @return null
	 */
	function attach(Table $table) {}
	
	/**
	 * Associate a left-side model with a right-side model
	 * 
	 * @param Model $left
	 * @param Model $right
	 * @return null
	 */
	function associate(Model $left, Model $right) {}
	
	/**
	 * Get the "right-side" objects associated with a "left-side" object
	 * 
	 * @param Model $left
	 * @return array|null associated objects
	 */
	function associated(Model $left) {}
	
	/**
	 * Called whenever a "left-side" model is constructed
	 * 
	 * @param Model $left
	 * @return array|null associated objects
	 */
	function autoload($left) 
	{
		return $this->eager ? $this->associated($left) : null;
	}
	
	/**
	 * Returned by the left model when a user accesses the association
	 * 
	 * @param Model $left optional
	 * @return Association $this
	 */
	function delegate($left = null) 
	{
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
	static $poly = true;
	
	function attach(Table $table) {
		$table->bind('beforeDelete', array($this, 'beforeDelete'));
	}
	
	function associate(Model $left, Model $right) {
		$left->addAttribute($this->foreignKey, $right->primaryKeyValue());
	}
	
	function associated(Model $left) {
		// get a model
		$rightClass = $this->rightClass;
		if ($id = $left->table()->get($this->foreignKey)) {
			return $rightClass::find($id);
		}
	}
}

?>