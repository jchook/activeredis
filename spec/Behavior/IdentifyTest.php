<?php

declare(strict_types=1);

namespace ActiveRedisSpec\Behavior;

use PHPUnit\Framework\TestCase;
use ActiveRedis\Behavior\Identify;
use ActiveRedis\Model;
use ActiveRedis\Table;

use ActiveRedisSpec\Support\MockModel;

/**
 * @covers Email
 */
final class IdentifyTest extends TestCase
{

	protected $nextId = 1;

	public function setUp()
	{
		$this->identify = new Identify([
			'fn' => [$this, 'getId'],
		]);
	}

	public function getId()
	{
		return $this->nextId++;
	}

	public function testId()
	{
		$id = $this->identify;
		$this->assertTrue(method_exists($id, 'afterConstruct'));
		$this->assertInstanceOf(Identify::class, $id);

		$m = new MockModel();
		// $nextId = $this->nextId;
		// $id->afterConstruct($t, $m);
		// $this->assertEquals($m->id, $nextId);
	}
}
