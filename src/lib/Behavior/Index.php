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

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function beforeDelete(TableInterface $table, Model $model): void
	{
		// Database
		$db = $model::db()->getConnection();

		// Get the model's DB key
		$modelKey = $table->getKey($model->getPrimaryKey());

		// For each of these...
		foreach ($this->getAttributes() as $attribute) {

			// Get the index key
			$indexKey = $table->getKey($model->getAttributes());

			// Remove it from the DB
			$db->srem($oldIndexKey, $modelKey);
		}
	}

	public function beforeSave(TableInterface $table, Model $model): void
	{
		// Get changed attributes (holds original values)
		$changed = $model->getChanged();

		// Did the key change?
		if (!array_intersect(array_keys($changed), $this->attributes)) {
			return;
		}

		// Database
		$db = $model::db()->getConnection();

		// Get the model's DB key
		$modelKey = $table->getKey($model->getPrimaryKey());

		// For each attribute(s) we should index...
		// TODO: these should support multiple columns
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
