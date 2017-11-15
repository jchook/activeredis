<?php

class B {
	public $test = 'true';
	public $ing = false;
}

class A {
	public $shalala = 'Hello';
}

$a = new A;

for ($i=0; $i<25; $i++) {
	$a->{$i} = mt_rand();
}

$a->b = new B;
$a->c = new B;
$a->d = new B;

$limit = 2000000;

$start = microtime(true);
for ($i = 0; $i < $limit; $i++) json_encode($a);
$end = microtime(true);
$ops = $limit / ($end - $start);
echo "$ops ops/sec - json_encode\n";

$start = microtime(true);
for ($i = 0; $i < $limit; $i++) serialize($a);
$end = microtime(true);
$ops = $limit / ($end - $start);
echo "$ops ops/sec - serialize\n";

$start = microtime(true);
$encoded = json_encode($a);
for ($i = 0; $i < $limit; $i++) json_decode($encoded);
$end = microtime(true);
$ops = $limit / ($end - $start);
echo "$ops ops/sec - json_decode\n";

$start = microtime(true);
$encoded = serialize($a);
for ($i = 0; $i < $limit; $i++) unserialize($encoded);
$end = microtime(true);
$ops = $limit / ($end - $start);
echo "$ops ops/sec - unserialize\n";