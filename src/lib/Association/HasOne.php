<?php

namespace ActiveRedis\Association;
use ActiveRedis\Model;

class HasOne extends AbstractAssociation
{
	function associate(Model $left, Model $right) {
		$left->setAttribute($this->foreignKey, $right->primaryKeyValue());
	}

	// TODO: do we need this? it's not currently being called by anything
	function beforeDelete($left) {
		$left->setAttribute($this->foreignKey, null);
	}

	function getAssociated(Model $left) {
		// get a model
		$rightClass = $this->rightClass;
		if ($id = $left->table()->get($this->foreignKey)) {
			// return $rightClass::find($id);
			// Need to write index classes
		}
	}
}
