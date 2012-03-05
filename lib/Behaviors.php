<?php

namespace ActiveRedis;

abstract class Behavior 
{
	static $bind;
	
	function __construct(Table $table = null, $options = null)
	{
		if ($options) $this->extend($options);
		if ($table) $this->attach($table);
	}
	
	function extend($options)
	{
		if (is_array($options))
			foreach ($options as $option => $value)
				if (is_string($option))
					$this->$option = $value;
	}
	
	// TODO: Good default functionality for attaching methods
	function attach(Table $table) 
	{
		if ($this::$bind) 
		{
			foreach ((array) $this::$bind as $callback) 
			{
				$table->bind($callback, array($this, $callback));
			}
		}
	}
}

class AutoTimestamp extends Behavior
{
	public function attach(Table $table)
	{
		$table->bind('beforeSave', __CLASS__ . '::beforeSave');
	}
	
	static function beforeSave($model)
	{
		if ($model->isNew()) 
		{
			$model->created_at = time();
		}
		$model->updated_at = time();
	}
}

class AutoAssociate extends Behavior
{
	function attach(Table $table) 
	{
		$table->bind('beforeSave', array($this, 'beforeSave'));
	}
	
	function beforeSave($model)
	{
		if (isset($this->done)) 
		{
			return;
		}
		
		$this->done = true;
		
		if ($associations = $model->table()->associations()) 
		{
			foreach ($associations as $name => $association) 
			{
				if (isset($model->$name) && $model->$name) 
				{
					$associatedModels = is_array($model->$name) ? $model->$name : array($model->$name);
					
					foreach ($associatedModels as $associatedModel) 
					{
						if (!is_object($associatedModel)) 
						{
							throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' expects associated model ' . $name . ' to be an object. Received ' . var_export($associatedModel,1));
						}
						
						$association->associate($model, $associatedModel);
					}
				}
			}
		}
	}
}

class SaveIndexes extends Behavior
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
	
	function beforeSave(&$model)
	{
		$dirty = array();
		
		if ($indexes = $this->indexes($model)) 
		{
			foreach ($indexes as $indexKey => $options) 
			{
				$index = $options[0];
				echo "indexKey: $indexKey\n";

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
	function beforeInsert(&$model)
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
	
	function afterSave(&$model)
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
					$model::table()->set($key, $model->id);
				}
			}
		}
	}
}

class DeepSave extends Behavior
{
	function attach(Table $table) 
	{
		$table->bind('beforeSave', __CLASS__ . '::beforeSave');
		$table->bind('afterSave', __CLASS__ . '::afterSave');
	}
	
	static function afterSave(&$model)
	{
		$model->meta('isDeepSaving', false);
	}
	
	static function beforeSave(&$model)
	{
		// Prevent recursion
		if (!$model->meta('isDeepSaving')) 
		{
			$model->meta('isDeepSaving', true);
			
			if ($associations = $model->table()->associations()) 
			{
				Log::info(get_class($model) . ' is deep-saving ' . json_encode(array_keys($associations)));
				foreach ($associations as $name => $association) 
				{
					if ($name && is_string($name) && ($associatedModels = $model->associated($name))) 
					{
						if (!is_array($associatedModels)) 
						{
							$associatedModels = array($associatedModels);
						}
						foreach ($associatedModels as $associatedModel) 
						{
							if (!is_object($associatedModel)) 
							{
								throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' expects associated model ' . $name . ' to be an object. Receivied ' . $associatedModel);
							}
						
							$associatedModel->save();
						}
					}
				}
			}
		}
	}
}


/**
 * Allows you to pay in queries to save space on large,
 * aging information
 * 
 * // Example configuration
 * static $behaviors = array('CacheAttributes' => array(
 * 	'ttl' => 1000, 
 * 	'attributes' => array(
 * 		'size' => array(
 * 			'ttl' => 500,
 * ))));
 */
class CacheAttributes extends Behavior
{
	public $attributes;
	public $ttl;
	public $renewOnSave;
	
	function __construct(Table $table = null, $options)
	{
		parent::__construct($table, $options);
		$this->attributes = $this::cleanAttributes($this->attributes);
	}
	
	function addAttributes($attr)
	{
		$this->attributes = array_merge($this::cleanAttributes($attributes), $this->attributes);
	}
	
	static function cleanAttributes($attributes)
	{
		$final = array();
		$attributes = (array) $attributes;
		foreach ($attributes as $var => $val) 
		{
			if (is_string($var)) 
				$final[$var] = $val;
			else 
				$final[$val] = array();
		}
		return $final;
	}
	
	function attach(Table $table)
	{
		if ($this->attributes)
			foreach (array('beforeSave', 'afterSave', 'afterFind') as $event)
				$table->bind($event, __CLASS__ . '::' . $event);
	}
	
	static function ttl($attribute = null)
	{
		if ($attribute && isset($this->attributes[$attribute]['ttl']))
			return $this->attributes[$attribute]['ttl'];
		return $this->ttl ?: 600;
	}
	
	static function afterFind($model)
	{
		foreach ($this->attributes as $attribute => $options)
			if ($value = $model->db()->get($model->key($attribute)))
				$model->$attribute = $value;
	}
	
	static function beforeSave($model)
	{
		// TODO: Group by TTL?
		$cached = array();
		foreach ($this->attributes as $attribute => $options) 
		{
			if ($model->isDirty($attribute) || ($this->renewOnSave && isset($model->$attribute))) 
			{
				$ttl = static::ttl($attribute);
				$model->db()->setex($model->key($attribute), $ttl, $model->$attribute);
				$cached[$attribute] = $model->$attribute;
				unset($model->$attribute);
			}
		}
		
		if ($cached)
			$model->meta(__CLASS__, $cached);
	}
	
	static function afterSave($model)
	{
		if ($cached = $model->meta(__CLASS__)) 
		{
			$model->meta(__CLASS__, false);
			$model->setAttributes($cached, false);
		}
	}
}

?>