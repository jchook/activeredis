<?php

namespace ActiveRedis;

class Inflector {
	
	public static function toSnakeCase($str) 
	{
		$str = lcfirst(strtr($str, " \t\n", '___'));
		return preg_replace_callback('/[a-z0-9][A-Z]/', function($matches){
			return $matches[0][0] . '_' . strtolower($matches[0][1]);
		}, $str);
	}
	
	public static function toCamelCase($str, $lowercaseFirst = false) 
	{
		$str = implode('', array_map('ucfirst', explode('_', $str)));
		return $lowercaseFirst ? lcfirst($str) : $str;
	}
	
	
	/**
	 * Code below copied from php-activerecord and modified
	 * 
	 * @link https://github.com/kla/php-activerecord/
	 * @link http://phpactiverecord.org/
	 * 
	 * Copyright (c) 2009
	 *
	 * AUTHORS:
	 * Kien La
	 * Jacques Fuentes
	 * 
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 * 
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 * 
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 * 
	 */
	private static $plural = array(
		'/(quiz)$/i'               => "$1zes",
		'/^(ox)$/i'                => "$1en",
		'/([m|l])ouse$/i'          => "$1ice",
		'/(matr|vert|ind)ix|ex$/i' => "$1ices",
		'/(x|ch|ss|sh)$/i'         => "$1es",
		'/([^aeiouy]|qu)y$/i'      => "$1ies",
		'/(hive)$/i'               => "$1s",
		'/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
		'/(shea|lea|loa|thie)f$/i' => "$1ves",
		'/sis$/i'                  => "ses",
		'/([ti])um$/i'             => "$1a",
		'/(tomat|potat|ech|her|vet)o$/i'=> "$1oes",
		'/(bu)s$/i'                => "$1ses",
		'/(alias)$/i'              => "$1es",
		'/(ax|test)is$/i'          => "$1es",
		'/(us)$/i'                 => "$1es",
		'/s$/i'                    => "s",
		'/$/'                      => "s"
	);

	private static $singular = array(
		'/(quiz)zes$/i'             => "$1",
		'/(matr)ices$/i'            => "$1ix",
		'/(vert|ind)ices$/i'        => "$1ex",
		'/^(ox)en$/i'               => "$1",
		'/(alias)es$/i'             => "$1",
		'/(cris|ax|test)es$/i'      => "$1is",
		'/(shoe)s$/i'               => "$1",
		'/(o)es$/i'                 => "$1",
		'/(bus)es$/i'               => "$1",
		'/([m|l])ice$/i'            => "$1ouse",
		'/(x|ch|ss|sh)es$/i'        => "$1",
		'/(m)ovies$/i'              => "$1ovie",
		'/(s)eries$/i'              => "$1eries",
		'/([^aeiouy]|qu)ies$/i'     => "$1y",
		'/([lr])ves$/i'             => "$1f",
		'/(tive)s$/i'               => "$1",
		'/(hive)s$/i'               => "$1",
		'/(li|wi|kni)ves$/i'        => "$1fe",
		'/(shea|loa|lea|thie)ves$/i'=> "$1f",
		'/(^analy)ses$/i'           => "$1sis",
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  => "$1$2sis",
		'/([ti])a$/i'               => "$1um",
		'/(n)ews$/i'                => "$1ews",
		'/(h|bl)ouses$/i'           => "$1ouse",
		'/(corpse)s$/i'             => "$1",
		'/(us)es$/i'                => "$1",
		'/(us|ss)$/i'               => "$1",
		'/s$/i'                     => ""
	);

	private static $irregular = array(
		'move'   => 'moves',
		'foot'   => 'feet',
		'goose'  => 'geese',
		'sex'    => 'sexes',
		'child'  => 'children',
		'man'    => 'men',
		'tooth'  => 'teeth',
		'person' => 'people'
	);

	private static $uncountable = array(
		'sheep',
		'fish',
		'deer',
		'series',
		'species',
		'money',
		'rice',
		'information',
		'equipment'
	);

	public static function pluralize( $string )
	{
		// save some time in the case that singular and plural are the same
		if ( in_array( strtolower( $string ), self::$uncountable ) )
			return $string;

		// check for irregular singular forms
		foreach ( self::$irregular as $pattern => $result )
		{
			$pattern = '/' . $pattern . '$/i';

			if ( preg_match( $pattern, $string ) )
				return preg_replace( $pattern, $result, $string);
		}

		// check for matches using regular expressions
		foreach ( self::$plural as $pattern => $result )
		{
			if ( preg_match( $pattern, $string ) )
				return preg_replace( $pattern, $result, $string );
		}

		return $string;
	}

	public static function singularize( $string )
	{
		// save some time in the case that singular and plural are the same
		if ( in_array( strtolower( $string ), self::$uncountable ) )
			return $string;

		// check for irregular plural forms
		foreach ( self::$irregular as $result => $pattern )
		{
			$pattern = '/' . $pattern . '$/i';

			if ( preg_match( $pattern, $string ) )
				return preg_replace( $pattern, $result, $string);
		}

		// check for matches using regular expressions
		foreach ( self::$singular as $pattern => $result )
		{
			if ( preg_match( $pattern, $string ) )
				return preg_replace( $pattern, $result, $string );
		}

		return $string;
	}

	public static function pluralize_if($count, $string)
	{
		if ($count == 1)
			return $string;
		else
			return self::pluralize($string);
	}
	
}

