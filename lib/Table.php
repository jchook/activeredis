<?php

namespace ActiveRedis;

class Table {
	
	public $name;
	public $class;
	public $callbacks;
	public $separator = ':';
	public $associations;
	
	function __construct(array $inject = null)  {
		if ($inject)
			foreach ($inject as $var => $val)
				$this->$var = $val;
		
		$this->associate();
	}
	
	static function db() {
		return Database::instance();
	}
	
	function bind($callbackName, $callback) {
		$this->callbacks[$callbackName][] = $callback;
		return $this;
	}
	
	function trigger($callbackName, $args = null) {
		$callbacks = isset($this->callbacks[$callbackName]) ? (array) $this->callbacks[$callbackName] : array();
		foreach ($callbacks as $callback) {
			if (call_user_func_array($callback, $args) === false) {
				return false;
			}
		}
	}
	
	function association($throughName) {
		if (isset($this->associations[$throughName])) {
			return $this->associations[$throughName];
		}
	}
	
	function associate($associations = null) {
		$associations = (array) ($associations ?: $this->associations);
		while($association = array_pop($associations)) {
			$options = null;
			if (is_array($association)) {
				list($association, $options) = $association;
			}
			if (is_string($association)) {
				list($associationClass, $modelClass) = explode(' ', $association);
				$association = new $associationClass($this->class, $modelClass, $options);
			}
			if (is_object($association) && method_exists($association, 'attach')) {
				$association->attach($this);
				$this->associations[$association->through] = $association;
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
	
	function updateIndex($field) {
		if (isset($this->attributes[$field])) {
			$this->db()->set($this->tableKey(array($field, $this->attributes[$field])), $this->primaryKeyValue());
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