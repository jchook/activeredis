<?php

declare(strict_types=1);

namespace ActiveRedis\Behavior;
use ActiveRedis\Model;
use ActiveRedis\Table;

class Identify extends AbstractBehavior
{
	public $attribute = 'id';
	public $fn = '\ActiveRedis\Uuid:v4()';

	static function beforeWrite(Table $table, Model $model): void
	{
		if (!$model->hasAttribute($this->attribute)) {
			$model->setAttribute($this->attribute, call_user_func($this->$fn));
		}
	}
}