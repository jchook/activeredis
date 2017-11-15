<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Model;

/**
 * @covers Database
 */
final class ModelTest extends TestCase
{
	public function testInstantiatesWithoutExceptions()
	{
		$a = new A();
		$this->assertInstanceOf(A::class, $a);
	}
}

class A extends Model {}