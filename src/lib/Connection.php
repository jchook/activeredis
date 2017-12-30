<?php

namespace ActiveRedis;

use Redis;

/**
 * Redis connection wrapper / compatability layer
 */
class Connection implements Configurable
{
	/**
	 * @var Redis
	 */
	protected $redis;

	/**
	 * @var string
	 */
	protected $host = '127.0.0.1';

	/**
	 * @var int
	 */
	protected $port = 6379;

	/**
	 * Ensure that a connection to the server exists. Note that if you inject a
	 * connection, it is assumed to be connected.
	 */
	public function touch(): void
	{
		if (!$this->redis) {

			// PHP Redis is a C extension of PHP that is required for ActiveRedis
			// If you do not have this installed, this line may fail. Make sure you
			// install the PHP Redis extension: https://github.com/phpredis/phpredis.
			$this->redis = $redis = new Redis();

			// Persistent connection
			// TODO: Configurable persistence? Why wouldn't you want persistence?
			$redis->pconnect($this->host, $this->port);

			// Automatically retry when scan returns something funky due to
			//unsolvable problems with concurrency
			$redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
		}
	}

	/**
	 * Configurable
	 */
	public function __construct(array $config = [])
	{
		foreach ($config as $var => $val) {
			$this->{$var} = $val;
		}
	}

	/**
	 * Forward to php-redis
	 * @link https://github.com/phpredis/phpredis
	 */
	public function __call($fn, array $args = [])
	{
		$this->touch();
		return call_user_func_array([$this->redis, $fn], $args);
	}
}
