<?php

namespace ActiveRedis;

class Adapter 
{	
	public $map;
	public $db;
	
	function __construct($config)
	{
		if (is_object($config))
			$config = array('db' => $config);
		if (is_array($config))
			foreach ($config as $var => $val)
				$this->$var = $val;
	}
	
	function __call($fn, $args)
	{
		$fn = @ $this->map[strtoupper($fn)] ?: $fn;
		$result = call_user_func_array(array($this->db, $fn), $args);
		Log::redis(strtoupper($fn) . ' ' . implode(' ', $args) . ' => ' . $result);
		return $result;
	}
}

?>