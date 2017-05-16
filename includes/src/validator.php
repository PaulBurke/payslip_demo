<?php

class validator
{
	public static $inv = false;

	public static function test($val)
	{
		$result = preg_match(static::$pattern, $val);

		if(static::$inv)
		{
			$result = !$result;
		}

		return $result;
	}
}