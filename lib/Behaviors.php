<?php

namespace ActiveRedis;

abstract class Behavior 
{
	function __construct(Table $table, $options)
	{
		$this->attach($table);
		$this->extend($options);
	}
	
	function extend($options)
	{
		if (is_array($options))
			foreach ($this->options as $option => $value)
				if (is_string($option))
					$this->$option = $value;
	}
	
	abstract function attach(Table $table);
}

class AutoTimestamp extends Behavior
{
	public function attach(Table $table)
	{
		$table->bind('beforeInsert', __CLASS__ . '::beforeInsert');
		$table->bind('beforeSave', __CLASS__ . '::beforeSave');
	}
	
	static function beforeInsert($model)
	{
		$model->created_at = time();
	}
	
	static function beforeSave($model)
	{
		$model->updated_at = time();
	}
}

class AutoAssociate extends Behavior
{
	function attach(Table $table) 
	{
		$table->bind('beforeSave', array($this, 'beforeSave'));
	}
	
	function beforeSave($model)
	{
		if (isset($this->done)) {
			return;
		}
		
		$this->done = true;
		
		if ($associations = $model->table()->associations()) 
		{
			foreach ($associations as $name => $association) 
			{
				if (isset($model->$name) && $model->$name) 
				{
					$associatedModels = is_array($model->$name) ? $model->$name : array($model->$name);
					
					foreach ($associatedModels as $associatedModel) 
					{
						if (!is_object($associatedModel)) 
						{
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' expects associated model ' . $name . ' to be an object. Received ' . var_export($associatedModel,1));
						}
						
						$association->associate($model, $associatedModel);
					}
				}
			}
		}
	}
}

class DeepSave extends Behavior
{
	function attach(Table $table) 
	{
		$table->bind('afterSave', __CLASS__ . '::afterSave');
	}
	
	static function afterSave($model)
	{	
		if ($associations = $model->table()->associations()) 
		{
			foreach ($associations as $name => $association) 
			{
				if (isset($model->$name) && $model->$name) 
				{
					$associatedModels = is_array($model->$name) ? $model->$name : array($model->$name);
					
					foreach ($associatedModels as $associatedModel) 
					{
						if (!is_object($associatedModel)) 
						{
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' expects associated model ' . $name . ' to be an object. Received ' . var_export($associatedModel,1));
						}
						
						$associatedModel->save();
					}
				}
			}
		}
	}
}

?>