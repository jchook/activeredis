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
		$table->bind('beforeSave', __CLASS__ . '::beforeSave');
	}
	
	static function beforeSave($model)
	{
		if ($model->isNew()) 
		{
			$model->created_at = time();
		}
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

class SaveIndexes extends Behavior
{
	function attach(Table $table)
	{
		$table->bind('afterSave', __CLASS__ . '::afterSave');
	}
	
	static function afterSave(&$model)
	{
		if ($indexes = (array) $model::$indexes) {
			foreach ($indexes as $index) {
				if ($model->hasAttribute($index)) {
					$model->table()->set(array($index, $model->$index), $model->id);
				} elseif ($model->isAssociated($index)) {
					throw new Exception('Association indexes are not yet supported. Try indexing the association ID instead.');
				} else {
					Log::warning(get_class($model) . ' indexes non-existent attribute ' . $index);
				}
			}
		}
	}
}

class DeepSave extends Behavior
{
	function attach(Table $table) 
	{
		$table->bind('beforeSave', __CLASS__ . '::beforeSave');
		$table->bind('afterSave', __CLASS__ . '::afterSave');
	}
	
	static function afterSave(&$model)
	{
		$model->meta('isDeepSaving', false);
	}
	
	static function beforeSave(&$model)
	{
		// Prevent recursion
		if (!$model->meta('isDeepSaving')) 
		{
			$model->meta('isDeepSaving', true);
			
			if ($associations = $model->table()->associations()) 
			{
				Log::info(get_class($model) . ' is deep-saving ' . json_encode(array_keys($associations)));
				foreach ($associations as $name => $association) 
				{
					if ($name && is_string($name) && ($associatedModels = $model->associated($name))) 
					{
						if (!is_array($associatedModels)) {
							$associatedModels = array($associatedModels);
						}
						foreach ($associatedModels as $associatedModel) 
						{
							if (!is_object($associatedModel)) 
							{
								if (is_array($associatedModel)) {
									print_r(array_keys($associatedModel));
								}
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' expects associated model ' . $name . ' to be an object. Receivied ' . $associatedModel);
							}
						
							$associatedModel->save();
						}
					}
				}
			}
		}
	}
}

?>