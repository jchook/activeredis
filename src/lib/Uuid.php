<?php

namespace ActiveRedis;

/**
 * UUID v4 Generator
 */
class Uuid
{
	const BUFFER_SIZE = 512;

	protected static $buf;
	protected static $bufIdx = self::BUFFER_SIZE;

	protected static $hexBytes = [
		"\x00" => '00',"\x01" => '01',"\x02" => '02',"\x03" => '03',"\x04" => '04',
		"\x05" => '05',"\x06" => '06',"\x07" => '07',"\x08" => '08',"\x09" => '09',
		"\x0a" => '0a',"\x0b" => '0b',"\x0c" => '0c',"\x0d" => '0d',"\x0e" => '0e',
		"\x0f" => '0f',"\x10" => '10',"\x11" => '11',"\x12" => '12',"\x13" => '13',
		"\x14" => '14',"\x15" => '15',"\x16" => '16',"\x17" => '17',"\x18" => '18',
		"\x19" => '19',"\x1a" => '1a',"\x1b" => '1b',"\x1c" => '1c',"\x1d" => '1d',
		"\x1e" => '1e',"\x1f" => '1f',"\x20" => '20',"\x21" => '21',"\x22" => '22',
		"\x23" => '23',"\x24" => '24',"\x25" => '25',"\x26" => '26',"\x27" => '27',
		"\x28" => '28',"\x29" => '29',"\x2a" => '2a',"\x2b" => '2b',"\x2c" => '2c',
		"\x2d" => '2d',"\x2e" => '2e',"\x2f" => '2f',"\x30" => '30',"\x31" => '31',
		"\x32" => '32',"\x33" => '33',"\x34" => '34',"\x35" => '35',"\x36" => '36',
		"\x37" => '37',"\x38" => '38',"\x39" => '39',"\x3a" => '3a',"\x3b" => '3b',
		"\x3c" => '3c',"\x3d" => '3d',"\x3e" => '3e',"\x3f" => '3f',"\x40" => '40',
		"\x41" => '41',"\x42" => '42',"\x43" => '43',"\x44" => '44',"\x45" => '45',
		"\x46" => '46',"\x47" => '47',"\x48" => '48',"\x49" => '49',"\x4a" => '4a',
		"\x4b" => '4b',"\x4c" => '4c',"\x4d" => '4d',"\x4e" => '4e',"\x4f" => '4f',
		"\x50" => '50',"\x51" => '51',"\x52" => '52',"\x53" => '53',"\x54" => '54',
		"\x55" => '55',"\x56" => '56',"\x57" => '57',"\x58" => '58',"\x59" => '59',
		"\x5a" => '5a',"\x5b" => '5b',"\x5c" => '5c',"\x5d" => '5d',"\x5e" => '5e',
		"\x5f" => '5f',"\x60" => '60',"\x61" => '61',"\x62" => '62',"\x63" => '63',
		"\x64" => '64',"\x65" => '65',"\x66" => '66',"\x67" => '67',"\x68" => '68',
		"\x69" => '69',"\x6a" => '6a',"\x6b" => '6b',"\x6c" => '6c',"\x6d" => '6d',
		"\x6e" => '6e',"\x6f" => '6f',"\x70" => '70',"\x71" => '71',"\x72" => '72',
		"\x73" => '73',"\x74" => '74',"\x75" => '75',"\x76" => '76',"\x77" => '77',
		"\x78" => '78',"\x79" => '79',"\x7a" => '7a',"\x7b" => '7b',"\x7c" => '7c',
		"\x7d" => '7d',"\x7e" => '7e',"\x7f" => '7f',"\x80" => '80',"\x81" => '81',
		"\x82" => '82',"\x83" => '83',"\x84" => '84',"\x85" => '85',"\x86" => '86',
		"\x87" => '87',"\x88" => '88',"\x89" => '89',"\x8a" => '8a',"\x8b" => '8b',
		"\x8c" => '8c',"\x8d" => '8d',"\x8e" => '8e',"\x8f" => '8f',"\x90" => '90',
		"\x91" => '91',"\x92" => '92',"\x93" => '93',"\x94" => '94',"\x95" => '95',
		"\x96" => '96',"\x97" => '97',"\x98" => '98',"\x99" => '99',"\x9a" => '9a',
		"\x9b" => '9b',"\x9c" => '9c',"\x9d" => '9d',"\x9e" => '9e',"\x9f" => '9f',
		"\xa0" => 'a0',"\xa1" => 'a1',"\xa2" => 'a2',"\xa3" => 'a3',"\xa4" => 'a4',
		"\xa5" => 'a5',"\xa6" => 'a6',"\xa7" => 'a7',"\xa8" => 'a8',"\xa9" => 'a9',
		"\xaa" => 'aa',"\xab" => 'ab',"\xac" => 'ac',"\xad" => 'ad',"\xae" => 'ae',
		"\xaf" => 'af',"\xb0" => 'b0',"\xb1" => 'b1',"\xb2" => 'b2',"\xb3" => 'b3',
		"\xb4" => 'b4',"\xb5" => 'b5',"\xb6" => 'b6',"\xb7" => 'b7',"\xb8" => 'b8',
		"\xb9" => 'b9',"\xba" => 'ba',"\xbb" => 'bb',"\xbc" => 'bc',"\xbd" => 'bd',
		"\xbe" => 'be',"\xbf" => 'bf',"\xc0" => 'c0',"\xc1" => 'c1',"\xc2" => 'c2',
		"\xc3" => 'c3',"\xc4" => 'c4',"\xc5" => 'c5',"\xc6" => 'c6',"\xc7" => 'c7',
		"\xc8" => 'c8',"\xc9" => 'c9',"\xca" => 'ca',"\xcb" => 'cb',"\xcc" => 'cc',
		"\xcd" => 'cd',"\xce" => 'ce',"\xcf" => 'cf',"\xd0" => 'd0',"\xd1" => 'd1',
		"\xd2" => 'd2',"\xd3" => 'd3',"\xd4" => 'd4',"\xd5" => 'd5',"\xd6" => 'd6',
		"\xd7" => 'd7',"\xd8" => 'd8',"\xd9" => 'd9',"\xda" => 'da',"\xdb" => 'db',
		"\xdc" => 'dc',"\xdd" => 'dd',"\xde" => 'de',"\xdf" => 'df',"\xe0" => 'e0',
		"\xe1" => 'e1',"\xe2" => 'e2',"\xe3" => 'e3',"\xe4" => 'e4',"\xe5" => 'e5',
		"\xe6" => 'e6',"\xe7" => 'e7',"\xe8" => 'e8',"\xe9" => 'e9',"\xea" => 'ea',
		"\xeb" => 'eb',"\xec" => 'ec',"\xed" => 'ed',"\xee" => 'ee',"\xef" => 'ef',
		"\xf0" => 'f0',"\xf1" => 'f1',"\xf2" => 'f2',"\xf3" => 'f3',"\xf4" => 'f4',
		"\xf5" => 'f5',"\xf6" => 'f6',"\xf7" => 'f7',"\xf8" => 'f8',"\xf9" => 'f9',
		"\xfa" => 'fa',"\xfb" => 'fb',"\xfc" => 'fc',"\xfd" => 'fd',"\xfe" => 'fe',
		"\xff" => 'ff',
	];

	public static function v4(): string
	{
		$b = self::v4bin();
		return self::$hexBytes[$b[0]] . self::$hexBytes[$b[1]] .
			self::$hexBytes[$b[2]] . self::$hexBytes[$b[3]] . '-' .
			self::$hexBytes[$b[4]] . self::$hexBytes[$b[5]] . '-' .
			self::$hexBytes[$b[6]] . self::$hexBytes[$b[7]] . '-' .
			self::$hexBytes[$b[8]] . self::$hexBytes[$b[9]] . '-' .
			self::$hexBytes[$b[10]] . self::$hexBytes[$b[11]] .
			self::$hexBytes[$b[12]] . self::$hexBytes[$b[13]] .
			self::$hexBytes[$b[14]] . self::$hexBytes[$b[15]]
		;
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
// 	uuid_create();
// }
// $end = microtime(true);
// $ops = $limit / ($end - $start);
// echo "$ops ops/sec\n";

// echo Uuid::v4();