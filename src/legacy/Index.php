<?php

namespace ActiveRedis;

abstract class Index extends Behavior 
{	
	protected $field;
	protected $store;
	
	abstract function value($model);
	abstract function save($model);	
}


class SimpleIndex extends Index
{
	function attach(Table $table)
	{
		$table->bind('afterSave', array($this, 'save'));
	}
	
	function key($model)
	{
		if ($this->field) {
			$key = $model->getAttribute($this->field);
			return $model->table()->key($key);
		}
		throw new Exception(get_class($this) . ' is missing a field to index for ' . get_class($model));
	}
	
	function value($model)
	{
		if ($this->store) {
			return $model->getAttribute($this->store);
		}
		return $model->primaryKeyValue();
	}
	
	function save($model)
	{
		return $model->db()->set($this->key($model), $this->value($model));
	}
}

