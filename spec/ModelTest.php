<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Model;
use ActiveRedisSpec\Support\MockModel;

/**
 * @covers Database
 */
final class ModelTest extends TestCase
{
	public function testInstantiatesWithoutExceptions()
	{
		$a = new MockModel();
		$this->assertInstanceOf(MockModel::class, $a);
	}
}
