<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Database;
use ActiveRedis\Network;

/**
 * @covers Email
 */
final class NetworkTest extends TestCase
{
	public function testSetGet()
	{
		$db = new Database();
		Network::set('default', $db);
		$this->assertEquals($db, Network::get('default'));
	}
}