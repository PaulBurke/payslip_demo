<?php

class attendance_record extends dbObj
{
	public $empid;
	public $id;
	public $timestamp;
	public $ux_timestamp;

	public $lower_bound;
	public $upper_bound;

	public $obj_values = ['timestamp', 'ux_timestamp'];


	public function getAll($empid, $lower, $upper)
	{
		if($lower > $upper)
		{
			$temp = $lower;
			$lower = $upper;
			$upper = $temp;
		}

		$this->lower_bound = $lower->format("Y-m-d H:i:s");
		$this->upper_bound = $upper->format("Y-m-d H:i:s");

		$this->empid = $empid;

		if(!$this->stmt_read_all)
		{
			$this->stmt_read_all = $this->link->prepare("SELECT `id`, `timestamp`, UNIX_TIMESTAMP(`timestamp`) FROM `attendance_records` WHERE `emp_id` = ? && `timestamp` > ? && `timestamp` < ? ORDER BY `timestamp` ASC");

			if(!$this->stmt_read_all)
			{
				$this->error = new errorAlert("ar1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_read_all->bind_param("iss", $this->empid, $this->lower_bound, $this->upper_bound);
			$this->stmt_read_all->bind_result($this->id, $this->timestamp, $this->ux_timestamp);
		}

		return parent::readAll();
	}
}