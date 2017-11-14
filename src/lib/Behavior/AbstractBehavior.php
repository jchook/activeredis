<?php

declare(strict_types=1);

namespace ActiveRedis\Behavior;

use ActiveRedis\Configurable;
use ActiveRedis\Model;

abstract class AbstractBehavior implements Configurable
{
	/**
	 * Standard configurable constructor
	 */
	function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}
	}

	public function handleEvent($eventName, $args): void
	{
		call_user_func_array($eventName, $args);
	}

	/**
	 * Helper that will give you a consistent, unique key for a model using its
	 * table and primary key.
	 */
	protected function getModelKey(Model $model): string
	{
		return $model->getTable()->getKey($model->getPrimaryKey());
	}
}
