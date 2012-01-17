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
	public $enforceUnique = true;
	public $ignoreCase = null;
	
	protected $indexes;
	
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
		}
		
		return \ActiveRedis\array_unique($this->indexes);
	}
	
	function afterConstruct(&$model)
	{
		if ($indexes = (array) $this->indexes($model)) 
		{
			$originals = array();
			foreach ($indexes as $index) 
			{
				if (is_array($index)) 
				{
					$originals = array_merge($originals, $model->getAttributes($index));
				}
				else
				{
					$originals[$index] = $model->attr($index);
				}
			}
		}
		$model->meta('originals', $originals);
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
			foreach ($indexes as $options) 
			{
				$index = $options[0];
				
				// index multiple things at once, e.g. User:username:$username:password:$password
				$dirty = false;
				if (is_array($index)) 
				{
					$key = $model->getAttributes($index);
					
					// Check to see if any of them are dirty
					foreach ($key as $attr => $value) 
					{
						if ($model->isDirty($attr)) 
						{
							$dirty = true;
							break;
						}
					}
				}
				
				elseif (is_string($index))
				{
					$dirty = $model->isDirty($index);
					$key = $model->getAttributes(array($index));
				}
				
				if ($dirty) 
				{
					if ($this->ignoreCase || (isset($options['ignoreCase']) && $options['ignoreCase']))
					{
						$key = array_map('strtolower', $key);
					}
					
					// Remove the old index
					if ($oldKey = array_intersect($model->meta('originals'), $key)) 
					{
						$model::table()->rem($oldKey);
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