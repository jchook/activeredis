<?php

declare(strict_types=1);

namespace ActiveRedis\Behavior;
use ActiveRedis\Model;
use ActiveRedis\Table;

class Timestamp extends AbstractBehavior
{
	protected $createdAt = 'createdAt';
	protected $updatedAt = 'updatedAt';

	public function beforeWrite(Table $table, Model $model): void
	{
		if (!isset($model->{$this->createdAt})) {
			$model->{$this->createdAt} = time();
		}
		$model->{$this->updatedAt} = time();
	}
}