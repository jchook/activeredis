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
	/**
	 * Whether to delete matched records
	 * @var bool
	 */
	protected $delete = false;

	/**
	 * @var array
	 */
	protected $insert = [];

	/**
	 * @var int
	 */
	protected $limit = 0;

	/**
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * @var array
	 */
	protected $select = [];

	/**
	 * @var array
	 */
	protected $update = [];

	/**
	 * @var array
	 */
	protected $where = [];

	/**
	 * Standard configurable constructor
	 */
	function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$setter = 'set' . ucfirst($var);
			if (method_exists($this, $setter)) {
				$this->$setter($val);
			}
		}
	}

	/*
	 * Getters
	 */

	public function getDelete(): bool
	{
		return $this->delete;
	}

	public function getInsert(): array
	{
		return $this->insert;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}

	public function getSelect(): array
	{
		return $this->select;
	}

	public function getUpdate(): array
	{
		return $this->update;
	}

	public function getWhere(): array
	{
		return $this->where;
	}

	/*
	 * "Has" functions
	 */

	public function hasDelete(): bool
	{
		return (bool) $this->delete;
	}

	public function hasInsert(): bool
	{
		return (bool) $this->insert;
	}

	public function hasLimit(): bool
	{
		return (bool) $this->limit;
	}

	public function hasOffset(): bool
	{
		return (bool) $this->offset;
	}

	public function hasSelect(): bool
	{
		return (bool) $this->select;
	}

	public function hasSelectAll(): bool
	{
		return in_array('*', $this->select);
	}

	public function hasUpdate(): bool
	{
		return (bool) $this->update;
	}

	public function hasWhere(): bool
	{
		return (bool) $this->where;
	}

	/*
	 * Setters
	 */

	public function setOffset(int $offset): void
	{
		$this->offset = $offset;
	}

	public function setDelete(bool $delete): void
	{
		$this->delete = $delete;
	}

	public function setInsert(array $insert): void
	{
		$this->insert = $insert;
	}

	public function setLimit(int $limit): void
	{
		$this->limit = $limit;
	}

	public function setSelect(array $select = ['*']): void
	{
		$this->select = $select;
	}

	public function setUpdate(array $update): void
	{
		$this->update = $update;
	}

	public function setWhere(array $where): void
	{
		$this->where = $where;
	}

}
