<?php

namespace ActiveRedisSpec\Support;

use ActiveRedis\Table\KeyTable;

class MockTable extends KeyTable
{
	protected $emitted = [];

	protected $modelClass = '\ActiveRedisSpec\Support\MockModel';

	public function emitEvent(string $eventName, array $args = []): bool
	{
		$this->emitted[] = compact('eventName', 'args');
		return true;
	}

	public function getEmitted()
	{
		return $this->emitted;
	}
}
