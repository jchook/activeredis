<?php

declare(strict_types=1);

namespace ActiveRedis\Table;

use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Configurable;
use ActiveRedis\Database;
use ActiveRedis\Model;
use ActiveRedis\Query;
use ActiveRedis\QueryResult;

/**
 *
 * Table
 *
 */
interface TableInterface
{
	/**
	 * Emit an event
	 * @param string $eventName
	 * @param array $args
	 * @return bool returns false if default is prevented
	 */
	public function emitEvent(string $eventName, array $args): bool;

	/**
	 * Get the association
	 */
	public function getAssociation(string $name): AbstractAssociation;

	/**
	 * Get all associations
	 */
	public function getAssociations(): array;

	/**
	 * Get the Database this Table belongs to
	 */
	public function getDatabase(): Database;

	/**
	 * Get the model class of objects stored in this table
	 * @return string
	 */
	public function getModelClass(): string;

	/**
	 * Get the name
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Whether the table has the named association.
	 * @param string $name
	 * @return bool
	 */
	public function hasAssociation(string $name): bool;

	/**
	 * Run a query against a table
	 */
	public function runQuery(Query $query): QueryResult;

}
