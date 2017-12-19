<?php

declare(strict_types=1);

namespace ActiveRedis\Table;

/**
 *
 * Paged result of a query
 *
 */
class BasicTableRefs implements Iterator
{
	/**
	 * Holds current array index
	 * @var int
	 */
	protected $key = 0;

	/**
	 * List of model refs
	 * @var array
	 */
	protected $refs = [];

	/**
	 * Current model instance
	 * @var Model?
	 */
	protected $current;

	/**
	 * Step
	 * @var int
	 */
	protected $step = 1;

	/**
	 * @var TableInterface
	 */
	protected $table;

	/**
	 * Valid
	 */
	protected $valid;

	/**
	 * Constructor
	 */
	public function __construct(TableInterface $table, array $refs)
	{
		$this->refs = $refs;
		$this->table = $table;
	}

	/**
	 * Get the current entry
	 * @return mixed
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
	 * Get the next thing
	 */
	public function next()
	{
		$this->key += $this->step;
		$this->loadCurrent();
	}

	/**
	 * Actually load the data from the DB
	 */
	protected function loadCurrent()
	{
		if ($this->valid()) {
			$this->current = $this->table->getModel($this->refs[$this->key]);
		}
	}

	/**
	 * Rewind is almost an initializer
	 */
	public function rewind()
	{
		$this->key = 0;
		$this->loadCurrent();
	}

	/**
	 * Is the current key valid?
	 */
	public function valid()
	{
		return isset($this->refs[$this->key]);
	}
}
