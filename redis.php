<?php

namespace ActiveRedis;

include 'ActiveRedis.php';


function parse_command($str)
{
	$final = array();
	$args = explode(' ', ltrim($str, ' '));
	$start = null;
	foreach ($args as $index => $arg) {
		if ($arg) {
			if (is_null($start)) {
				if (substr($arg, 0, 1) == '"') {
					$start = $index;
				} else {
					$final[] = $arg;
				}
			} else {
				if (substr($arg, -1) == '"') {
					if (substr($arg, -2) !== '\"') {
						$final[] = substr(implode(' ', array_intersect_key($args, array_flip(range($start, $index)))), 1, -1);
						$start = null;
					}
				}
			}
		}
	}
	return $final;
}

function format_response($data)
{
	if (is_string($data)) {
		return $data;
	}
	return json_encode($data);
}

$db = new Connection(parse_url('tcp://127.0.0.1:6379'));

echo $prompt = "> ";
while ($line = trim(fgets(STDIN))) {
	$args = parse_command($line);
	$cmd = array_shift($args);
	try {
		echo format_response(call_user_func_array(array($db, $cmd), $args));
	} catch (Exception $e) {
		echo $e->getMessage(); // . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
	}
	echo "\n$prompt";
}

?>