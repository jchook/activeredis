<?php

namespace ActiveRedisSpec\Support;

use ActiveRedis\Connection;

class MockConnection extends Connection
{
	protected $calls = [];

	public function __construct(array $config = [])
	{
		// Ahhh...
	}

	public function __call($fn, $args)
	{
		$this->calls[] = [$fn, $args];
	}

	public function popCalls()
	{
		$calls = $this->calls;
		$this->calls = [];
		return $calls;
	}
}