<?php

namespace ActiveRedis;

class Adapter\PhpRedis extends Adapter
{
	protected $db;
	protected $config;
	protected $initialized;
	
	function __construct($config = null, $redisObject = null)
	{
		// Allow user to provide a Redis object
		if ($redisObject) {
			$this->db = $redisObject;
			$this->initialized = true;
		} 
		
		// But make a new Redis by default
		else {
			$this->db = new \Redis;
		}
		
		// Store the config for later
		if ($config) {
			if (!is_array($config)) {
				$config['connect'] = $config;
			}
			$this->config = $config;
		}
	}
	
	function initialize()
	{
		if (!$this->initialized) {
			$init = isset($this->config['initialize']) ? (array) $this->config['initialize'] : array('connect', 'open', 'pconnect', 'popen');
			foreach ($init as $fn) {
				if (is_string($fn)) {
					if (isset($this->config[$fn])) {
						call_user_func_array(array($this->db, $fn), ActiveRedis\array_force($this->config[$fn]));
					}
				} elseif (is_callable($fn)) {
					$fn($this);
				}
			}
		}
	}
	
	function __call($fn, $args)
	{
		$this->initialize();
		return parent::__call($fn, $args);
	}
}

?>