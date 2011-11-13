<?php

namespace ActiveRedis;

abstract class Behavior 
{
	abstract function attach(Table $table);
}

class AutoTimestamp extends Behavior
{
	public static function attach(Table $table)
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
		$table->bind('beforeInsert', function($model) {
			
		});
	}
	
	function beforeSave($model) 
	{
		
	}
}

?>