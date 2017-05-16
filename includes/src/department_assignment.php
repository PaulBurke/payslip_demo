<?php

class department_assignment extends dbStatusObj
{
	public $id;
	public $empid;
	public $dept_id;
	public $dept_name;
	public $date;

	public function statusAtDate($date = false)
	{
		if(!intCheck::test($this->empid))
		{
			$this->error = new errorAlert("dep0", "$this->empid is not a valid employee ID.\nAn employee ID should only contain numbers",
										$_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		if(!$date)
		{
			$date = new DateTime("now",system_constants::getTimezone());
		}

		if($this->date && $date <= $this->date)
		{
			return true;
		}

		$this->cur_date = $date->format("Y-m-d");

		if(!isset($this->stmt_status_at_date))
		{
			$this->stmt_status_at_date = $this->link->prepare("
			SELECT a.`id`, a.`dept_id`, a.`date`, b.`name` FROM `assignment_department` a
			LEFT JOIN
				`departments` b
			ON
				a.`dept_id` = b.`id`
			WHERE a.`emp_id` = ? && a.`date` <= ?
			ORDER BY a.`date` DESC LIMIT 1");

			if(!$this->stmt_status_at_date)
			{
				$this->error = new errorAlert("dep1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_status_at_date->bind_param("is", $this->empid, $this->cur_date);
			$this->stmt_status_at_date->bind_result($this->id, $this->dept_id, $this->date, $this->dept_name);
		}

		if(!$this->stmt_status_at_date->execute())
		{
			$this->error = new errorAlert("dep2", $this->stmt_status_at_date->error, $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->stmt_status_at_date->store_result();

		if($this->stmt_status_at_date->num_rows < 1)
		{
			$this->no_more_records = true;
			return true;
		}

		$this->stmt_status_at_date->fetch();

		$this->date = new DateTime($this->date, system_constants::getTimezone());

		return true;
	}
}