<?php

declare(strict_types=1);

namespace ActiveRedis\Table;

use ActiveRedis\Query;


/**
 *
 * A page of results
 *
 */
class BasicPaginator implements Iterator
{
	/**
	 * @var ?BasicPage
	 */
	protected $currentPage;

	/**
	 * @var int
	 */
	protected $key = 0;

	/**
	 * @var Query
	 */
	protected $query;

	/**
	 * @var TableInterface
	 */
	protected $table;


	/**
	 * Constructor
	 */
	public function __construct(BasicTable $table, Query $query)
	{
		$this->table = $table;
		$this->query = $query;
		$this->nextPage();
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
		if ($this->valid()) {
			++$this->key;
			$this->currentPage->next();
			if (!$this->currentPage->valid()) {
				$this->nextPage();
			}
		}
	}

	/**
	 * Get the next page of things
	 */
	public function nextPage()
	{
		$queryResult = $this->table->query($this->query);
		$this->query = $queryResult->getNextPageQuery();
		$this->currentPage = $queryResult->getResult();
	}

	/**
	 * Rewind is almost an initializer
	 */
	public function rewind()
	{
		$this->key = 0;
		$this->query->setOffset(0);
		$this->currentPage = null;
		$this->nextPage();
	}

	/**
	 * Is the current key valid?
	 */
	public function valid()
	{
		return $this->currentPage && $this->currentPage->valid();
	}
}
