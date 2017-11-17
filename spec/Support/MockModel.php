<?php

namespace ActiveRedisSpec\Support;

use ActiveRedis\Model;

class MockModel extends Model
{

	/**
	 * Use the mock database
	 */
	protected static $db = 'mock';
	protected $emitted = [];

	public function emitEvent(string $eventName, array $args = []): void
	{
		$this->emitted[] = compact('eventName', 'args');
	}

	public function getEmitted()
	{
		return $this->emitted;
	}
}