<?php

class shift_pattern_assignment extends dbStatusObj
{
	public $id;
	public $shift_pattern_id;
	public $shift_pattern_name;
	public $workdays; 			// How many days a week are considered work days.
	public $date;
	public $dept_id;
	public $off_days;
	public $month;
	public $workdays_in_month; 	// How many workdays there'll be in a month.

	public function statusAtDate($date = false)
	{
		if(!intCheck::test($this->dept_id))
		{
			$this->error = new errorAlert("spa0", "$this->dept_it is not a valid Department.",
										$_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		if(parent::statusAtDate($date))
		{
			return true;
		}

		$this->month = false; // So that daysInMonth will recalculate based on the new shift_pattern.

		if(!isset($this->stmt_status_at_date))
		{
			$this->stmt_status_at_date = $this->link->prepare("
			SELECT a.`id`, a.`sp_id`, a.`date`, b.`name`, c.`workdays`, GROUP_CONCAT(d.`day_no`) AS 'off_days' FROM `shift_pattern_assignment` a
			LEFT JOIN
				`shift_patterns` b
			ON
				a.`sp_id` = b.`id`
			LEFT JOIN
				(SELECT `sp_id`, COUNT(`sp_id`) AS 'workdays' FROM `shift_pattern_details` WHERE `day_off` = 0 GROUP BY `sp_id`) c
			ON
				a.`sp_id` = c.`sp_id`
			LEFT JOIN
				`shift_pattern_details` d
			ON
				a.`sp_id` = d.`sp_id` && d.`day_off` = 1
			WHERE a.`department` = ? && a.`date` <= ?
			ORDER BY a.`date` DESC LIMIT 1");

			if(!$this->stmt_status_at_date)
			{
				$this->error = new errorAlert("spa1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_status_at_date->bind_param("is", $this->dept_id, $this->cur_date);
			$this->stmt_status_at_date->bind_result($this->id, $this->shift_pattern_id, $this->date, $this->shift_pattern_name, $this->workdays, $this->off_days);
		}

		return parent::getStatusAtDate();
	}

	public function workdaysInMonth($date, $force = false)
	{
		$month = intval($date->format("m"));

		// Don't need to calculate again if we've already worked it out for this month.

		if(!$force && intval($month) == $this->month)
		{
			return $this->workdays_in_month;
		}

		$this->month = intval($month);

		$workdays_in_month = 4 * $this->workdays; // There's always going to be a minimum of 28 days in a month, hence a minimum of 4 weeks.

		if($date->format("t") == 28)
		{
			return $this->workdays_in_month;
		}

		$year = $date->format("Y");

		$timezone = system_constants::getTimezone();

		$start = new DateTime($year."-".$month."-29", $timezone);
		$end = new DateTime($year."-".$month."-".$date->format("t"), $timezone);

		$one_day = new DateInterval("P1D");

		while($start <= $end)
		{
			if(strpos($start->format("N"), $this->off_days) !== false)
			{
				$workdays_in_month += 1;
			}

			$start->add($one_day);
		}

		return $this->workdays_in_month;
	}
}