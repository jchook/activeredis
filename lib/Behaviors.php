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
		$table->bind('beforeInsert', function($model){
			$model->created_at = time();
		});
		$table->bind('beforeSave', function($model) {
			$model->updated_at = time();
		});
	}
}

class DeepSave extends Behavior
{
	function attach(Table $table) 
	{
		$table->bind('beforeSave', __CLASS__ . '::beforeSave');
	}
	
	static function beforeSave($model)
	{
		if ($associated = $model->associated()) 
		{
			foreach ($associated as $associationName => $associatedModels) 
			{
				$associatedModels = (array) $associatedModels;
				foreach ($associatedModels as $associatedModel) 
				{
					if (!is_object($associatedModel)) 
					{
						throw new Exception(__CLASS__ . ' expects associated models to be objects. ' . $associatedModel);
					}
					
					$associatedModel->save();
				}
			}
		}
		
		Log::temp(var_export($associated, 1));
	}
}

?>