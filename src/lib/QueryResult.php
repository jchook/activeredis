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
	 * @var ?Exception
	 * @deprecated... why are we catching and attaching exceptions?? let them go.
	 */
	protected $exception;

	/**
	 * @var Iterator
	 */
	protected $result;

	/**
	 * Next page query
	 * @link https://github.com/phpredis/phpredis#sscan
	 */
	protected $nextPageQuery;

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

	public function getResult(): Iterator
	{
		return $this->result;
	}

	public function getNextPageQuery()
	{
		return $this->nextPageQuery;
	}

	public function isSuccessful(): bool
	{
		return ! $this->exception;
	}
}
