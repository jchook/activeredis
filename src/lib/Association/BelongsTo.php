<?php

namespace ActiveRedis\Association;
use ActiveRedis\Exception\ModelNotFound;

class BelongsTo extends AbstractAssociation
{
	function associate(Model $left, Model $right)
	{
		$left->setAttribute($this->foreignKey, $right->getDbKey());
	}

	function getAssociated(Model $left)
	{
		$rightClass = $this->rightClass;
		$table = $rightClass::table();
		try {
			return $table->getModel($this->foreignKey);
		} catch (ModelNotFound $e) {}
	}
}