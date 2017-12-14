<?php

declare(strict_types=1);

namespace ActiveRedis\Behavior;
use ActiveRedis\Model;
use ActiveRedis\Table\TableInterface;

/**
 * Save indexes to a model so that it can be located
 */
class Index extends AbstractBehavior
{
	protected $attributes = [];

	public function beforeWrite(TableInterface $table, Model $model): void
	{
		// Database
		$db = $model::db()->getConnection();

		// Get the model's DB key
		$modelKey = $model->getDbKey();

		// Get changed attributes (holds original values)
		$changed = $model->getChanged();

		// For each attribute(s) we should index...
		foreach ($this->attributes as $attrName) {

			// Did this value even change?
			if (!array_key_exists($attrName, $changed)) {
				continue;
			}

			// Old index key
			$oldIndexKey = $table->getKey([
				$attrName => $changed[$attrName]
			]);

			// Remove this model from the index
			$db->srem($oldIndexKey, $modelKey);

			// New index key
			$indexKey = $table->getKey(
				$model->getAttributes((array)$attrName)
			);

			// Add this model to the index
			$db->sadd($indexKey, $modelKey);
		}
	}
}
