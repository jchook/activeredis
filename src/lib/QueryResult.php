<?php

declare(strict_types=1);

namespace ActiveRedis;

/**
 *
 * Query
 *
 */
class QueryResult implements Configurable
{
	/**
	 * @var Exception
	 */
	protected $exception;

	/**
	 * @var Iterator
	 */
	protected $iterator;

	/**
	 * Standard configurable constructor
	 */
	function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}

		// Default iterator
		if (!$this->iterator) {
			$this->iterator = new ArrayIterator([]);
		}
	}

	public function getException(): ?Exception
	{
		return $this->exception;
	}

	public function getIterator(): Iterator
	{
		return $this->iterator;
	}

	public function isSuccessful(): bool
	{
		return ! $this->exception;
	}
}
