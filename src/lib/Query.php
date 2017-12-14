<?php

declare(strict_types=1);

namespace ActiveRedis;

use ActiveRedis\Table\TableInterface;

/**
 *
 * Query
 *
 */
class Query implements Configurable
{
	protected $delete;
	protected $insert;
	protected $limit;
	protected $offset;
	protected $select;
	protected $update;
	protected $where;

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
