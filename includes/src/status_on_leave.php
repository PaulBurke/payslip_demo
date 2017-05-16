<?php

class status_on_leave extends dbStatusObj
{
	public $id;
	public $empid;
	public $on_leave;
	public $date;

	public function statusAtDate($date = false)
	{
		if(!intCheck::test($this->empid))
		{
			$this->error = new errorAlert("ool0", "$this->empid is not a valid employee ID.\nAn employee ID should only contain numbers",
										$_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		parent::statusAtDate($date);

		if(!isset($this->stmt_status_at_date))
		{
			$this->stmt_status_at_date = $this->link->prepare("
			SELECT `id`, `on_leave`, `date` FROM `status_on_leave`
			WHERE `emp_id` = ? && `date` <= ?
			ORDER BY `date` DESC LIMIT 1");

			if(!$this->stmt_status_at_date)
			{
				$this->error = new errorAlert("ool1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_status_at_date->bind_param("is", $this->empid, $this->cur_date);
			$this->stmt_status_at_date->bind_result($this->id, $this->on_leave, $this->date);
		}

		return parent::getStatusAtDate();
	}
}