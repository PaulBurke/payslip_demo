<?php

class system_constants
{
	public static $timezone_name = "Asia/Riyadh";
	public static $week_day_start = 6; // Sunday - 1 = Monday, 7 = Sunday.

	public static function getTimezone()
	{
		return new DateTimeZone(self::$timezone_name);
	}
}