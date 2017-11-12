<?php

namespace ActiveRedis;

class Table {
	
	protected static $instances;
	
	public $name; // name of this table
	public $model; // class name
	public $database; // name
	public $separator = ':'; // key separator
	public $callbacks;
	public $behaviors = array('AutoAssociate' => true, 'AutoTimestamp' => true, 'DeepSave' => true, 'SaveIndexes' => true);
	public $attributes;
	public $associations;
	public $primaryKey;
	
	function __construct(array $inject = null)  
	{
		// Index of non-injected vars
		$build = array_flip(array('behaviors', 'associations'));
		
		// Standard injection service
		if ($inject)
			foreach ($inject as $var => $val)
				if (is_string($var) && !isset($build[$var]))
					$this->$var = $val;
		
		// Add associations
		if (isset($inject['associations'])) {
			$this->addAssociations($inject['associations']);
		}
		
		// Add behaviors
		if (isset($inject['behaviors'])) {
			$this->addBehaviors($inject['behaviors'] ?: $this->behaviors);
		}
		
		// Experimental: Attach the model as its own behavior
		// $model = $this->model;
		// $model::attach($this);
		
		Log::debug($this->model . ' Table loaded');
	}
	
	static function instance($class)
	{
		if (!isset(static::$instances[$class])) 
		{
			if (!class_exists($class)) {
				throw new Exception('Cannot create table for non-existent model class: ' . $class);
			}
			if (!is_array($class::$table)) {
				$class::$table = array('name' => $class::$table);
			}
			static::$instances[$class] = new Table(array_merge(array(
				'name' => basename(strtr($class, "\\", '/')),
				'model' => $class,
				'associations' => $class::$associations ?: array(),
				'behaviors' => $class::$behaviors ?: array(),
				'callbacks' => $class::$callbacks ?: array(),
				'primaryKey' => $class::$primaryKey ?: 'id',
			), $class::$table));
		}
		return static::$instances[$class];
	}
	
	/**
	 * Get the current database instance to use for this table
	 * 
	 * @return Database
	 */
	function db() 
	{
		return Database::instance($this->database);
	}
	
	/**
	 * Bind a callback to an event
	 * 
	 * @param string $eventName
	 * @param callback $callback
	 * @return $this
	 */
	function bind($eventName, $callback) 
	{
		$this->callbacks[$eventName][] = $callback;
		return $this;
	}
	
	/**
	 * Trigger an event. Runs all callbacks bound to the event.
	 * If a callback returns false, it will stop propagation of
	 * the calling event.
	 * 
	 * @param string $eventName
	 * @param mixed $args
	 * @return $result
	 */
	function trigger($eventName, $args = null) 
	{
		$result = null;
		$callbacks = isset($this->callbacks[$eventName]) ? (array) $this->callbacks[$eventName] : array();
		
		if ($callbacks) 
		{	
			Log::vebug($this->model . ' start triggering ' . count($callbacks) . ' callbacks for ' . $eventName);
			
			foreach ($callbacks as $index => &$callback) {
				Log::vebug($this->model . ' --> ' . $eventName . ' ' . $index . ' : ' . $callback);
				if (($result = call_user_func_array($callback, $args)) === false) {
					return false;
				}
			}
			
			Log::vebug($this->model . ' done triggering ' . count($callbacks) . ' callbacks for ' . $eventName);
		}
		
		return $result;
	}
	
	/**
	 * Get an Association object by name
	 * 
	 * @param string $throughName
	 * @return mixed Association | null
	 */
	function association($throughName) 
	{
		if (isset($this->associations[$throughName])) {
			Log::vebug($this->model . ' Table association(' . $throughName . ') found');
			return $this->associations[$throughName];
		}
		Log::vebug($this->model . ' Table association(' . $throughName . ') does not exist');
	}
	
	/**
	 * Return the array of all association objects
	 * @return array
	 */
	function associations() 
	{
		return (array) $this->associations;
	}
	
	/**
	 * Utility to find a class. Tests to see if the
	 * class is within any of the given namespaces
	 * 
	 * @param string $basename class name without namespaces
	 * @param mixed $namespaces string | array
	 * @return mixed string | null
	 */
	function findClass($basename, $namespaces = '') 
	{
		if (class_exists($basename, false)) {
			return $basename;
		}
		$namespaces = (array) $namespaces;
		$basename = trim($basename, '\\');
		$attempts = array();
		foreach ($namespaces as $namespace) 
		{
			$className = $namespace . '\\' . $basename;
			if (class_exists($className)) {
				return ltrim($className, '\\');
			} else {
				$attempts[] = $className;
			}
		}
		Log::warning('Class ' . $basename . ' could not be resolved. Attempted ' . json_encode($attempts));
	}
	
	/**
	 * Add a set of behaviors to this table
	 * 
	 * Ex $behaviors:
	 * 'BehaviorClass'
	 * array('BehaviorClass' => $options, 'AnotherBehaviorClass')
	 * 
	 * $options is passed as the first argument for the behavior class's constructor
	 * 
	 * @param mixed $behaviors
	 * @return null
	 * @throws Exception
	 */
	function addBehaviors($behaviors)
	{
		if ($behaviors) {	
			$behaviors = (array) $behaviors;
			
			foreach ($behaviors as $id => $val) {
				
				// Get everything in the correct format
				if (is_string($id)) {
					$behavior = $id;
					$options = $val;
				} else {
					$options = (array) $val;
					$behavior = (string) array_shift($options);
				}
				
				// Remove the behavior by setting options to false
				if ($options === false) {
					if (isset($this->behaviors[$behavior]))
						unset($this->behaviors[$behavior]);
					Log::vebug($this->model . ' removed behavior ' . $behaviorClass);
				} 
				
				// Otherwise guess the namespace and add the behavior
				else {
					if (!($behaviorClass = $this->findClass($behavior, array(get_namespace($this->model), __NAMESPACE__)))) {
						throw new Exception($this->model . ' table is unable to locate behavior ' . $behavior);
					}
					$this->behaviors[$behavior] = new $behaviorClass($this, $options);
					Log::vebug($this->model . ' attached behavior ' . $behaviorClass);
				}
			}
		}
	}
	
	/**
	 * Add a set of associations to this table
	 * 
	 * Ex $associations:
	 * 'MonoAssociationClass ModelClass'
	 * 'PolyAssociationClass ModelClasses'
	 * array(array('AssociationClass', 'config' => 'options', 'follow' => true), 'SimpleAssociationClass')
	 * 
	 * @param mixed $associations
	 * @return null
	 * @throws Exception
	 */
	function addAssociations($associations) 
	{
		if ($associations) 
		{
			$associations = (array) $associations;
			
			while($association = array_pop($associations)) 
			{	
				$options = null;
			
				if (is_array($association)) {
					$options = $association;
					$association = array_shift($options);
				}
			
				if (is_string($association)) {
					list($associationType, $associatedClass) = explode(' ', $association);
					$associationType = $this->findClass($associationType, array(get_namespace($this->model), __NAMESPACE__));
					if ($associationType::$poly) {
						$associatedClass = Inflector::singularize($associatedClass);
					}
					$associatedClass = $this->findClass($associatedClass, get_namespace($this->model));
					if ($associationType && $associatedClass) {
						$association = new $associationType($this->model, $associatedClass, $options);
					}
				}
			
				if (is_object($association) && method_exists($association, 'attach')) {
					$association->attach($this);
					$this->associations[$association->name] = $association;
					Log::debug($this->name . ' associated with ' . $association->name);
				} else {
					throw new Exception('Invalid association ' . $this->model . ' ' . $association);
				}
			}
		}
	}
	
	/**
	 * Get a single full key for a record in the table
	 * 
	 * @param mixed $subkeys any number of subkeys. nested arrays are flattened.
	 * @return string
	 */
	function key($subkeys = null)  {
		if (is_array($subkeys)) {
			ksort($subkeys);
			$subkeys = array_estrange($subkeys, false);
		}
		return implode($this->separator, array_flatten(array($this->name, $subkeys)));
	}
	
	function del($id) {
		return $this->db()->del($this->key($id));
	}
	
	function set($id, $value) {
		return $this->db()->set($this->key($id), $value);
	}
	
	function get($id) {
		return $this->db()->get($this->key($id));
	}
	
	function exists($id) {
		return $this->db()->exists($this->key($id));
	}
	
	function insert(Model $model) {
		$this->trigger('beforeInsert', array($model));
		if (!$model->isNew()) {
			throw new Exception('Cannot insert a non-new record. Use update instead.');
		}
		if (!$model->primaryKeyValue()) {
			$model->primaryKeyValue($this->nextUnique($model->primaryKey()));
		}
		$result = $this->set($model->primaryKeyValue(), $model->serialize());
		$this->trigger('afterInsert', array($model, $result));
		return $result;
	}
	
	function update(Model $model) {
		$this->trigger('beforeUpdate', array($model));
		if ($model->isNew()) {
			throw new Exception('Cannot update a new record. Use insert instead.');
		}
		$result = $this->set($model->primaryKeyValue(), $model->serialize());
		$this->trigger('afterUpdate', array($model, $result));
		return $result;
	}
	
	function nextUnique($type = 'id') {
		$table = $this->name;
		return $this->db()->incr("unique:$table:$type");
	}
	
	function __toString() {
		return $this->name;
	}
	
}

