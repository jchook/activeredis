<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Database;
use ActiveRedis\Provider;

/**
 * @covers Email
 */
final class ProviderTest extends TestCase
{
	public function testSetGet()
	{
		$db = new Database();
		Provider::setDatabase('default', $db);
		$this->assertEquals($db, Provider::getDatabase('default'));
	}
}