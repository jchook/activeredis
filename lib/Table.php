<?php

namespace ActiveRedis;

class Table {
	
	public $name;
	public $model; // class name
	public $database; // name
	public $callbacks;
	public $separator = ':';
	public $associations;
	
	function __construct(array $inject = null)  {
		if ($inject)
			foreach ($inject as $var => $val)
				$this->$var = $val;
		
		Log::debug('Table loaded');
		
		$this->buildAssociations();
	}
	
	static function db() {
		return Database::instance();
	}
	
	function bind($callbackName, $callback) {
		$this->callbacks[$callbackName][] = $callback;
		return $this;
	}
	
	function trigger($callbackName, $args = null) {
		$result = null;
		$callbacks = isset($this->callbacks[$callbackName]) ? (array) $this->callbacks[$callbackName] : array();
		foreach ($callbacks as $callback) {
			if (($result = call_user_func_array($callback, $args)) === false) {
				return false;
			}
		}
		return $result;
	}
	
	function association($throughName) {
		if (isset($this->associations[$throughName])) {
			Log::debug($this->model . ' table -> association(' . $throughName . ') found');
			return $this->associations[$throughName];
		}
		Log::debug($this->model . ' table -> association(' . $throughName . ') does not exist ' . json_encode($this->associations));
	}
	
	function associations() {
		return (array) $this->associations;
	}
	
	function findClass($basename, $namespaces) {
		if (class_exists($basename, false)) {
			return $basename;
		}
		$namespaces = (array) $namespaces;
		$basename = trim($basename, '\\');
		$attempts = array();
		foreach ($namespaces as $namespace) {
			$className = $namespace . '\\' . $basename;
			if (class_exists($className)) {
				return $className;
			} else {
				$attempts[] = $className;
			}
		}
		Log::warning('Class ' . $basename . ' could not be resolved. Attempted ' . json_encode($attempts));
	}
	
	function buildAssociations($associations = null) 
	{
		$associations = (array) ($associations ?: $this->associations);
		
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
	
	function key($subkeys = null)  {
		return implode($this->separator, array_merge((array) $this->name, (array) $subkeys));
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
	
	function updateIndex(Model $model, $field) {
		if (isset($this->attributes[$field])) {
			$this->db()->set($this->key(array($field, $this->attributes[$field])), $this->primaryKeyValue());
		}
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