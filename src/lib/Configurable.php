<?php

declare(strict_types=1);

namespace ActiveRedis;

interface Configurable
{
	public function __construct(array $config = []);
}
