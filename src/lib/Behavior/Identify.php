<?php

declare(strict_types=1);

namespace ActiveRedis\Behavior;
use ActiveRedis\Model;
use ActiveRedis\Table;

/**
 * Automatically assign a globally unique ID to a model when it's created.
 */
class Identify extends AbstractBehavior
{
	/**
	 * @var string
	 */
	public $attribute = 'id';

	/**
	 * @var callable
	 */
	public $fn = '\ActiveRedis\Uuid:v4()';

	/**
	 * @var boolean
	 */
	public $changed = true;

	/**
	 * As soon as the model is created, make sure it has an ID
	 */
	public function afterConstruct(Table $table, Model $model): void
	{
		if (!$model->hasAttribute($this->attribute)) {
			$model->setAttribute(
				$this->attribute,
				call_user_func($this->fn),
				$this->changed
			);
		}
	}
}