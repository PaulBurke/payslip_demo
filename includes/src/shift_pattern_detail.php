<?php

class shift_pattern_detail extends dbObj
{
	public $id;
	public $sp_id;
	public $day_no;
	public $day_start;
	public $lunch_start;
	public $lunch_duration;
	public $day_end;
	public $sign_in_cut_off;
	public $day_off;
	public $holiday;
	public $holiday_name;

	public $cur_date;

	protected $obj_values = ['id', 'day_start', 'lunch_start', 'lunch_duration', 'day_end', 'sign_in_cut_off', 'day_off', 'holiday', 'holiday_name'];

	public function get($sp_id, $date = false)
	{
		if(!$date)
		{
			$date = new DateTime("now", system_constants::getTimezone());
		}

		$this->sp_id = $sp_id;

		if(!intCheck::test($this->sp_id))
		{
			$this->error = new errorAlert("spd0", "$this->sp_id is not a valid Shift Pattern identifier.",
										$_SERVER['PHP_SELF'],__LINE__);
			return false;
		}


		$this->day_no = $date->format("N");

		$this->cur_date = $date->format("Y-m-d");

		if(!$this->stmt_read)
		{
			$this->stmt_read = $this->link->prepare("SELECT a.`id`, a.`day_start`, a.`lunch_start`, a.`lunch_duration`, a.`day_end`, a.`sign_in_cut_off`, a.`day_off`, b.`id`, b.`name`
				FROM
					`shift_pattern_details` a
				LEFT JOIN
					`holidays` b
				ON
					b.`date` = ?
				WHERE a.`sp_id` = ? && a.`day_no` = ?");

			if(!$this->stmt_read)
			{
				$this->error = new errorAlert("spd1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_read->bind_param("sii", $this->cur_date, $this->sp_id, $this->day_no);
			$this->stmt_read->bind_result($this->id, $this->day_start, $this->lunch_start, $this->lunch_duration, $this->day_end, $this->sign_in_cut_off, $this->day_off, $this->holiday, $this->holiday_name);
		}

		return parent::read();
	}
}