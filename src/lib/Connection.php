<?php

/**
 * This code is based on Redisent, a Redis interface for the modest.
 *
 * @author Justin Poliey <jdp34@njit.edu>
 * @copyright 2009 Justin Poliey <jdp34@njit.edu>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace ActiveRedis;

/**
 * Redisent, a Redis interface for the modest among us
 */
class Connection implements Configurable
{
	const CRLF = "\r\n";

	protected $connection = false;
	protected $config;

	public function  __construct(array $config = array())
	{
		$this->config = $config;
	}

	public function  __destruct()
	{
		if ($this->connection) {
			fclose($this->connection);
		}
	}

	public function touch()
	{
		if (!$this->connection) {
			if (false == ($this->connection = @fsockopen($this->config['host'] ?? 'localhost', $this->config['port'] ?? 6379, $errno, $errstr))) {
				throw new \Exception($errstr, $errno);
			}
			unset($this->config);
		}
	}

	public function __call($name, $args)
	{
		$this->touch();

		$response = null;

		$name = strtoupper($name);

		$command = '*' . (count($args) + 1) . self::CRLF;
		$command .= '$' . strlen($name) . self::CRLF;
		$command .= $name . self::CRLF;

		foreach ($args as $arg) {
			$command .= '$' . strlen($arg) . self::CRLF;
			$command .= $arg . self::CRLF;
		}

		fwrite($this->connection, $command);

		$reply = trim(fgets($this->connection, 512));

		switch (substr($reply, 0, 1)) {

			// Error
			case '-':
				throw new \Exception(substr(trim($reply), 4));
			break;

			// In-line reply
			case '+':
				$response = substr(trim($reply), 1);
			break;

			// Bulk reply
			case '$':
				if ($reply == '$-1') {
					$response = null;
					break;
				}
				$read = 0;
				$size = substr($reply, 1);
				do {
					$block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
					$response .= fread($this->connection, $block_size);
					$read += $block_size;
				} while ($read < $size);
				fread($this->connection, 2);
			break;

			// Mult-Bulk reply
			case '*':
				$count = substr($reply, 1);
				if ($count == '-1') {
					return null;
				}
				$response = array();
				for ($i = 0; $i < $count; $i++) {
					$bulk_head = trim(fgets($this->connection, 512));
					$size = substr($bulk_head, 1);
					if ($size == '-1') {
						$response[] = null;
					}
					else {
						$read = 0;
						$block = "";
						do {
							$block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
							$block .= fread($this->connection, $block_size);
							$read += $block_size;
						} while ($read < $size);
						fread($this->connection, 2); /* discard crlf */
						$response[] = $block;
					}
				}
			break;

			// Integer Reply
			case ':':
				$response = substr(trim($reply), 1);
			break;

			// Don't know what to do?  Throw it outta here
			default:
				throw new \Exception("invalid server response: {$reply}");
			break;
		}

		return $response;
	}

}