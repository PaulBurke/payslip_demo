<?php

class shift_pattern_assignment extends dbStatusObj
{
	public $id;
	public $shift_pattern_id;
	public $shift_pattern_name;
	public $date;
	public $dept_id;

	public function statusAtDate($date = false)
	{
		parent::statusAtDate($date);

		if(!isset($this->stmt_status_at_date))
		{
			$this->stmt_status_at_date = $this->link->prepare("
			SELECT a.`id`, a.`sp_id`, a.`date`, b.`name` FROM `shift_pattern_assignment` a
			LEFT JOIN
				`shift_patterns` b
			ON
				a.`sp_id` = b.`id`
			WHERE a.`department` = ? && a.`date` <= ?
			ORDER BY a.`date` DESC LIMIT 1");

			if(!$this->stmt_status_at_date)
			{
				$this->error = new errorAlert("spa1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_status_at_date->bind_param("is", $this->dept_id, $this->cur_date);
			$this->stmt_status_at_date->bind_result($this->id, $this->shift_pattern_id, $this->date, $this->shift_pattern_name);
		}

		return parent::getStatusAtDate();
	}
}