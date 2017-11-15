<?php

namespace ActiveRedis;

/**
 * UUID v4 Generator
 */
class Uuid
{
	// Buffering random_bytes() speeds up generation of many uuids at once
	const BUFFER_SIZE = 512;

	protected static $buf;
	protected static $bufIdx = self::BUFFER_SIZE;

	public static function v4(): string
	{
		$b = self::randomBytes(16);
		$b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
		$b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
	}

	public static function v4bin(): string
	{
		$b = self::randomBytes(16);
		$b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
		$b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
		return $b;
	}

	protected static function randomBytes(int $n): string
	{
		if (self::$bufIdx + $n >= self::BUFFER_SIZE) {
			self::$buf = random_bytes(self::BUFFER_SIZE);
			self::$bufIdx = 0;
		}
		$idx = self::$bufIdx;
		self::$bufIdx += $n;
		return substr(self::$buf, $idx, $n);
	}
}


// ----------------------------------------------------

// Code used to generate 2-byte hex codes
// $alpha = '0123456789abcdefg';
// for ($i = 0; $i < 16; $i++) {
// 	for ($j = 0; $j < 16; $j++) {
// 		echo '\'' . $alpha[$i] . $alpha[$j] . '\',';
// 	}
// 	echo "\n";
// }


// ----------------------------------------------------


// Code used to generate string => string 2-byte hex codes
// $bytes = [
//   '00','01','02','03','04','05','06','07','08','09','0a','0b','0c','0d','0e','0f',
//   '10','11','12','13','14','15','16','17','18','19','1a','1b','1c','1d','1e','1f',
//   '20','21','22','23','24','25','26','27','28','29','2a','2b','2c','2d','2e','2f',
//   '30','31','32','33','34','35','36','37','38','39','3a','3b','3c','3d','3e','3f',
//   '40','41','42','43','44','45','46','47','48','49','4a','4b','4c','4d','4e','4f',
//   '50','51','52','53','54','55','56','57','58','59','5a','5b','5c','5d','5e','5f',
//   '60','61','62','63','64','65','66','67','68','69','6a','6b','6c','6d','6e','6f',
//   '70','71','72','73','74','75','76','77','78','79','7a','7b','7c','7d','7e','7f',
//   '80','81','82','83','84','85','86','87','88','89','8a','8b','8c','8d','8e','8f',
//   '90','91','92','93','94','95','96','97','98','99','9a','9b','9c','9d','9e','9f',
//   'a0','a1','a2','a3','a4','a5','a6','a7','a8','a9','aa','ab','ac','ad','ae','af',
//   'b0','b1','b2','b3','b4','b5','b6','b7','b8','b9','ba','bb','bc','bd','be','bf',
//   'c0','c1','c2','c3','c4','c5','c6','c7','c8','c9','ca','cb','cc','cd','ce','cf',
//   'd0','d1','d2','d3','d4','d5','d6','d7','d8','d9','da','db','dc','dd','de','df',
//   'e0','e1','e2','e3','e4','e5','e6','e7','e8','e9','ea','eb','ec','ed','ee','ef',
//   'f0','f1','f2','f3','f4','f5','f6','f7','f8','f9','fa','fb','fc','fd','fe','ff',
// ];

// $n = 0;
// $alpha = '0123456789abcdefg';
// for ($i = 0; $i < 16; $i++) {
//   for ($j = 0; $j < 16; $j++) {
//     echo '"\x' . $bytes[$n] . '" => \'' . $alpha[$i] . $alpha[$j] . '\',';
//     if (($n % 5) == 4) {
//       echo "\n";
//     }
//     $n++;
//   }
// }


// ----------------------------------------------------


// Code used to decide whether to use random_bytes or openssl module

// $limit = 5000000;
// $time = microtime(true);
// for ($i = 0; $i < $limit; $i++) {
// 	openssl_random_pseudo_bytes(128);
// }
// echo (microtime(true) - $time) . "\topenssl_random_pseudo_bytes";

// $time = microtime(true);
// for ($i = 0; $i < $limit; $i++) {
// 	random_bytes(128);
// }
// echo (microtime(true) - $time) . "\trandom_bytes";


// $time = microtime(true);
// for ($i = 0; $i < ceil($limit/2); $i++) {
// 	random_bytes(256);
// }
// echo (microtime(true) - $time) . "\trandom_bytes buffered x2";

// $time = microtime(true);
// for ($i = 0; $i < ceil($limit/4); $i++) {
// 	random_bytes(512);
// }
// echo (microtime(true) - $time) . "\trandom_bytes buffered x4";

// $time = microtime(true);
// for ($i = 0; $i < ceil($limit/8); $i++) {
// 	random_bytes(1024);
// }
// echo (microtime(true) - $time) . "\trandom_bytes buffered x8";


// ----------------------------------------------------


// Benchmark
// $limit = 20000000;
// $start = microtime(true);
// for ($i = 0; $i < $limit; $i++) {
// 	Uuid::v4();
// }
// $end = microtime(true);
// $ops = $limit / ($end - $start);
// echo "$ops ops/sec\n";

// $limit = 2000000;
// $start = microtime(true);
// for ($i = 0; $i < $limit; $i++) {
// 	\uuid_create();
// }
// $end = microtime(true);
// $ops = $limit / ($end - $start);
// echo "$ops ops/sec\n";

// echo Uuid::v4();