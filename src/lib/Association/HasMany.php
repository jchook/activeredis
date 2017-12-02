<?php

namespace ActiveRedis\Association;
use ActiveRedis\Model;

class HasMany extends AbstractAssociation
{
	static $poly = true;

	function associate(Model $left, Model $right)
	{
		$right->setAttribute($this->foreignKey, $left->getDbKey());
	}

	function getAssociated(Model $left)
	{
		$rightClass = $this->rightClass;
		return $rightClass::findBy([ $this->foreignKey => $left->getDbKey() ]);
	}
}
