<?php

declare(strict_types=1);

namespace ActiveRedis;

/**
 *
 * Query
 *
 */
class Query implements Configurable
{
	public $fn;
	public $args;

	/**
	 * Standard configurable constructor
	 */
	function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}
	}
}