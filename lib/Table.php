<?php

namespace ActiveRedis;

class Table {
	
	protected static $instances;
	
	public $name;
	public $model; // class name
	public $database; // name
	public $callbacks;
	public $behaviors;
	public $separator = ':';
	public $associations;
	
	function __construct(array $inject = null)  
	{
		$build = array_flip(array('behaviors', 'associations'));
		
		if ($inject)
			foreach ($inject as $var => $val)
				if (is_string($var) && !isset($build[$var]))
					$this->$var = $val;
		
		if (isset($inject['associations'])) {
			$this->addAssociations($inject['associations']);
		}
		if (isset($inject['behaviors'])) {
			$this->addBehaviors($inject['behaviors']);
		}
		
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
	
	static function db() 
	{
		return Database::instance();
	}
	
	function bind($callbackName, $callback) 
	{
		$this->callbacks[$callbackName][] = $callback;
		return $this;
	}
	
	function trigger($callbackName, $args = null) 
	{
		$result = null;
		$callbacks = isset($this->callbacks[$callbackName]) ? (array) $this->callbacks[$callbackName] : array();
		
		if ($callbacks) 
		{	
			Log::vebug($this->model . ' start triggering ' . count($callbacks) . ' callbacks for ' . $callbackName);
			
			foreach ($callbacks as $index => $callback) {
				Log::vebug($this->model . ' --> ' . $callbackName . ' ' . $index . ' : ' . $callback);
				if (($result = call_user_func_array($callback, $args)) === false) {
					return false;
				}
			}
			
			Log::vebug($this->model . ' done triggering ' . count($callbacks) . ' callbacks for ' . $callbackName);
		}
		
		return $result;
	}
	
	function association($throughName) 
	{
		if (isset($this->associations[$throughName])) {
			Log::vebug($this->model . ' Table association(' . $throughName . ') found');
			return $this->associations[$throughName];
		}
		Log::vebug($this->model . ' Table association(' . $throughName . ') does not exist');
	}
	
	function associations() 
	{
		return (array) $this->associations;
	}
	
	function findClass($basename, $namespaces) 
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
			if (class_exists($className, false)) {
				return $className;
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
		if ($behaviors) 
		{	
			$behaviors = (array) $behaviors;
			
			foreach ($behaviors as $id => $val)
			{
				if (is_string($id)) {
					$behavior = $id;
					$options = $val;
				} else {
					$options = (array) $val;
					$behavior = (string) array_shift($options);
				}
				if (!($behaviorClass = $this->findClass($behavior, array(get_namespace($this->model), __NAMESPACE__)))) {
					throw new Exception($this->model . ' table is unable to locate behavior ' . $behavior);
				}
			
				$this->behaviors[$behavior] = new $behaviorClass($this, $options);
			
				Log::vebug($this->model . ' attached behavior ' . $behaviorClass);
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
	
	function key($subkeys = null)  {
		return implode($this->separator, array_flatten(array_merge((array) $this->name, (array) $subkeys)));
	}
	
	function set($id, $value) {
		return $this->db()->set($this->key($id), $value);
	}
	
	function get($id) {
		return $this->db()->get($this->key($id));
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

?>