<?php

declare(strict_types=1);

namespace ActiveRedis;

/**
 *
 * Query
 *
 */
class ModelPointers implements Iterator
{
	/**
	 * Holds current array index
	 * @var int
	 */
	protected $key = 0;

	/**
	 * List of model keys
	 * @var array
	 */
	protected $dbKeys = [];

	/**
	 * Current model instance
	 * @var Model?
	 */
	protected $current;

	/**
	 * Get the current entry
	 * @var mixed
	 */
	public function current()
	{
		return $this->current;
	}

	/**
	 * Get the current key
	 * @return int
	 */
	public function key()
	{
		return $this->key;
	}

	/**
	 * [next description]
	 * @return function [description]
	 */
	public function next()
	{

	}

	public function rewind()
	{
	}

	public function valid()
	{
	}
}
