<?php

namespace ActiveRedis;

class SaveIndexes extends AbstractBehavior
{
	static $bind = array('afterConstruct', 'beforeInsert', 'beforeSave', 'afterSave');
	
	public $enforceUnique = true;
	public $ignoreCase = null;
	
	protected $indexes;
	protected $indexedAttributes;
	
	// 'username',
	// array('username')
	// array(array('username', 'password'),  /* options */ ),
	function indexes(&$model = null)
	{
		if ($model && is_null($this->indexes)) {
			
			$this->indexes = array();
		
			if ($indexes = (array) $model::$indexes) 
			{
				foreach ($indexes as $indexKey => $index) 
				{
					// allow shorthand
					if (is_string($index))
						$index = array($index);
					// enforce format
					elseif (!isset($index[0]))
						continue;
					
					$this->indexes[] = $index;
				}
			}
			
			$this->indexes = \array_unique($this->indexes);
		}
		
		return $this->indexes;
	}
	
	function afterConstruct($model)
	{
		if ($model->isNew()) {
			$originals = array();
		} else {
			$indexedAttributes = $this->indexedAttributes($model);
			$originals = $this->indexValues($model, $indexedAttributes);
		}
		$model->meta('SaveIndexesOriginals', $originals);
	}
	
	protected function indexedAttributes($model)
	{
		if ($model && is_null($this->indexedAttributes)) {
			$indexedAttributes = array();
			if ($indexes = $this->indexes($model)) {
				foreach ($indexes as $indexKey => $options) {
					$indexedAttributes = \array_merge($indexedAttributes, (array) $options[0]);
				}
			}
			$this->indexedAttributes = \array_unique($indexedAttributes);
		}
		return $this->indexedAttributes;
	}
	
	protected function indexValues($model, $index)
	{
		$storageValue = array();
		$raw = $model->getAttributes((array) $index);
		foreach ($raw as $attr => $val) {
			if (is_object($val) && isset($val->id)) {
				$storageValue[$attr] = $val->id;
			} else {
				$storageValue[$attr] = $val;
			}
		}
		return $storageValue;
	}
	
	function beforeSave($model)
	{
		$dirty = array();
		
		if ($indexes = $this->indexes($model)) 
		{
			foreach ($indexes as $indexKey => $options) 
			{
				$index = $options[0];

				// index multiple things at once, e.g. User:username:$username:password:$password
				if (is_array($index)) 
				{
					// Check to see if any of them are dirty
					foreach ($index as $attr) 
					{
						if ($model->isNew() || $model->isDirty($attr)) 
						{
							$dirty[] = $indexKey;
							break;
						}
					}
				}

				elseif (is_string($index))
				{
					if ($model->isDirty($index)) {
						$dirty[] = $indexKey;
					}
				}
			}
		}
		
		$model->meta('SaveIndexesDirty', $dirty);
	}
	
	/**
	 * Enforces unique indexes
	 * 
	 * @throws Duplicate
	 */
	function beforeInsert($model)
	{
		if (!$this->enforceUnique) 
			return;
		if ($indexes = $this->indexes($model)) 
			foreach ($indexes as $index)
				if (isset($index['unique']) && $index['unique']) 
					if ($conditions = $model->getAttributes($index[0]))
						if ($model::exists($conditions))
							throw new Duplicate;
	}
	
	function afterSave($model)
	{
		if ($indexes = $this->indexes($model)) 
		{
			if ($dirty = $model->meta('SaveIndexesDirty')) 
			{	
				foreach ($dirty as $indexKey) 
				{	
					$options = $indexes[$indexKey];
					$index = $options[0];
					
					$key = $model->getAttributes((array) $index);

					if ($this->ignoreCase || (isset($options['ignoreCase']) && $options['ignoreCase']))
					{
						$key = array_map('strtolower', $key);
					}
					
					// Remove the old index
					if ($oldKey = array_intersect_key($model->meta('SaveIndexesOriginals'), $key)) 
					{
						$model::table()->del($oldKey);
					}
					
					// Set the new index
					if ($this->enforceUnique || (isset($options['unique']) && $options['unique'])) {
						$model::table()->set($key, $model->id);
					} else {
						$model::table()->set($key, $model->id);
					}
					
				}
			}
		}
	}
}