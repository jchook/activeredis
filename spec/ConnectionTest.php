<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Connection;

/**
 * @covers Email
 */
final class ConnectionTest extends TestCase
{

	public function setUp()
	{
		$this->conn = new Connection();
	}

	public function testConnectsToRedis()
	{
		$conn = $this->conn;
		$this->assertInstanceOf(Connection::class, $conn);
		$this->assertEquals($conn->echo('test'), 'test');
	}
}
