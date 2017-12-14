<?php

declare(strict_types=1);

namespace ActiveRedis\Behavior;
use ActiveRedis\Model;
use ActiveRedis\Table\TableInterface;

class Timestamp extends AbstractBehavior
{
	protected $createdAt = 'createdAt';
	protected $updatedAt = 'updatedAt';

	public function beforeWrite(TableInterface $table, Model $model): void
	{
		if (!isset($model->{$this->createdAt})) {
			$model->{$this->createdAt} = time();
		}
		$model->{$this->updatedAt} = time();
	}
}
