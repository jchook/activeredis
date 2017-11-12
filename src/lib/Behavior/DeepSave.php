<?php

namespace ActiveRedis;

class DeepSave extends AbstractBehavior
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
				Log::vebug(get_class($model) . ' ' . get_called_class() . ' ' . json_encode(array_keys($associations)));
				foreach ($associations as $name => $association) 
				{
					if ($name && is_string($name) && ($associatedModels = $model->associated($name))) 
					{
						if (!is_array($associatedModels)) 
						{
							$associatedModels = array($associatedModels);
						}
						foreach ($associatedModels as $associatedModel) 
						{
							if (!is_object($associatedModel)) 
							{
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
