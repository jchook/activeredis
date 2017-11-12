<?php

namespace ActiveRedis\Association;

class HasMany extends AbstractAssociation
{
	static $poly = true;

	function associate(Model $left, Model $right)
	{
		// $left->addAttribute($this->foreignKey, $right->primaryKeyValue());
		// Wow this is bad, let's revisit this
	}

	function getAssociated(Model $left)
	{
		// get a model
		$rightClass = $this->rightClass;
		if ($id = $left::table()->get($this->foreignKey)) {
			return $rightClass::find($id);
		}
	}
}

