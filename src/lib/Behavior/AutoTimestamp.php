<?php

namespace ActiveRedis;

class AutoTimestamp extends AbstractBehavior
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