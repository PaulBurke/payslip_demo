<?php

class dbStatusObj extends dbObj
{
	public $cur_date;
	public $date;

	public $no_more_records = false;

	protected $stmt_status_at_date;

	public function statusAtDate($date = false)
	{		
		if(!$date)
		{
			$date = new DateTime("now",system_constants::getTimezone());
		}

		if($this->date && $date <= $this->date)
		{
			return true;
		}

		$this->cur_date = $date->format("Y-m-d");
	}

	public function getStatusAtDate($property = "date")
	{
		if(!$this->stmt_status_at_date->execute())
		{
			$this->error = new errorAlert("status1", $this->stmt_status_at_date->error, $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->stmt_status_at_date->store_result();

		if($this->stmt_status_at_date->num_rows < 1)
		{
			$this->no_more_records = true;
			return true;
		}

		$this->stmt_status_at_date->fetch();

		$this->date = new DateTime($this->{$property}, system_constants::getTimezone());

		return true;
	}
}