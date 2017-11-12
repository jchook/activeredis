<?php

namespace ActiveRedis;

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
class CacheAttributes extends AbstractBehavior
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

