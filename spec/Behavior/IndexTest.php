<?php

declare(strict_types=1);

namespace ActiveRedisSpec\Behavior;

use PHPUnit\Framework\TestCase;
use ActiveRedis\Behavior\Index;
use ActiveRedisSpec\Support\MockModel;
use ActiveRedisSpec\Support\MockTable;

/**
 * @covers Email
 */
final class IndexTest extends TestCase
{
	public function testIndex()
	{
		$index = new Index([
			'attributes' => ['name', 'height'],
		]);

		$model = new MockModel([
			'attributes' => [
				'id' => 1,
				'name' => 'Wes Roberts',
				'height' => 200,
			],
		]);

		$table = $model::table();
		$dbKey = $model->getDbKey();

		// First make sure our assumptions are correct about IDs
		$this->assertEquals($table->getKey($model->getPrimaryKey()), 'db:mocks?id=1');

		// Old key
		$oldNameKey = $table->getKey($model->getAttributes(['name']));

		// Now change the model
		$model->setAttribute('name', 'Joshua Contare');

		// New key
		$newNameKey = $table->getKey($model->getAttributes(['name']));

		// They should be different
		$this->assertTrue($oldNameKey !== $newNameKey);

		// Index the change
		$index->handleEvent('beforeWrite', [$table, $model]);

		// See what the connection did
		$calls = $model::db()->getConnection()->popCalls();
		$this->assertEquals($calls, [
			['srem', [$oldNameKey, $dbKey]],
			['sadd', [$newNameKey, $dbKey]],
		]);
	}
}

