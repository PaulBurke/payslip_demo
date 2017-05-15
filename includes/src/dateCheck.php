<?php
class dateCheck extends validator
{
	public static $pattern = "/^[1-2][0,1,2,3,9][0-9][0-9]\-([0][1-9]|[1][0-2])\-([0][1-9]|[1-2][0-9]|[3][0-1])$/";
}