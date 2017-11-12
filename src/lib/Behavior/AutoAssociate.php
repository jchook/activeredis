<?php

namespace ActiveRedis;

class AutoAssociate extends AbstractBehavior
{
	function attach(Table $table) 
	{
		$table->bind('beforeSave', array($this, 'beforeSave'));
	}
	
	function beforeSave($model)
	{
		if (isset($this->done)) 
		{
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