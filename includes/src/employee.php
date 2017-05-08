<?php

// Class for handling employee details. Will pull information from the database base on supplied employee ID.

class employee extends dbObj
{
	public $id;		// employee ID
	public $name;	// employee name
	public $email;	// employee email

	public $status = []; // This will hold the status information for the relevant dates.

	/*
	status_dept, status_on_leave and shift_pattern are all holders
	for classes which will be creted further down in. They're stored
	here in case they're respective functions are called upon out of
	sequence to save generating them multiple times.
	*/

	private $status_dept;		
	private $status_on_leave;	
	private $shift_pattern;
	private $contract_details;

	public function __construct($id = false, &$link = false)
	{
		if(!parent::__construct($link))
		{
			return false;
		}

		if($id)
		{
			$this->id = $id;
			return $this->read();
		}

	}

	public function read()
	{
		if(!$this->stmt_read)
		{
			if(!$this->stmt_read = $this->link->prepare("SELECT `name`, `email` FROM `employees` WHERE `id` = ?"))
			{
				$this->error = new errorAlert("emp1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_read->bind_param("i", $this->id);
			$this->stmt_read->bind_result($this->name, $this->email);
		}

		return parent::read();
	}

	public function getStatus($start, $end = false)
	{
		$cur_date = clone $start;

		if(!$end)
		{
			$end = $start;
		}

		$one_day = new DateInterval("P1D"); //1 Day interval;

		if(!$this->status_dept)
		{
			if(!$this->status_dept = new department_assignment($this->link))
			{
				$this->error = $this->status_dept->error;
				return false;
			}
		}


		if(!$this->status_on_leave)
		{
			if(!$this->status_on_leave = new status_on_leave($this->link))
			{
				$this->error = $this->status_on_leave->error;
				return false;
			}
		}

		$this->status_dept->empid = $this->id;
		$this->status_on_leave->empid = $this->id;

		$status_obj = new stdClass;

		/*
		status_obj will hold the results of the on_leave and department status queries
		This information is then stored in the $this->status array, keyed by the date value
		for access later depending on the task being performed. For example, getShiftPattern will
		use the department information to determine the shift pattern of the employee.
		*/

		$status_obj->on_leave = false;
		$status_obj->on_leave_record = NULL;
		$status_obj->on_leave_date = NULL;
		$status_obj->dept_id = NULL;
		$status_obj->dept_record = NULL;
		$status_obj->dept_name = NULL;
		$status_obj->dept_date = NULL;

		while($cur_date <= $end)
		{
			if(!$this->status_dept->statusAtDate($cur_date))
			{
				$this->error = $this->status_dept->error;
				return false;
			}

			if(!$this->status_on_leave->statusAtDate($cur_date))
			{
				$this->error = $this->status_on_leave->error;
				return false;
			}

			$cur_status = clone $status_obj;

			$this->status[$cur_date->format("Y-m-d")] = $cur_status;

			$cur_status->on_leave = $this->status_on_leave->on_leave;
			$cur_status->on_leave_record = $this->status_on_leave->id;
			$cur_status->on_leave_date = $this->status_on_leave->date;
			$cur_status->dept_id = $this->status_dept->dept_id;
			$cur_status->dept_name = $this->status_dept->dept_name;
			$cur_status->dept_records = $this->status_dept->id;
			$cur_status->dept_date = $this->status_dept->date;

			$cur_date->add($one_day);
		}

		return $this;
	}

	public function getShiftPatterns($start, $end = false)
	{
		$cur_date = clone $start;

		if(!$end)
		{
			$end = $start;
		}

		$one_day = new DateInterval("P1D"); //1 Day interval;

		if(!$this->shift_pattern)
		{
			if(!$this->shift_pattern = new shift_pattern_assignment($this->link))
			{
				$this->error = $this->shift_pattern->error;
				return false;
			}
		}

		while($cur_date <= $end)
		{
			$date = $cur_date->format("Y-m-d");

			if(!isset($this->status[$date]))
			{
				if(!$this->getStatus($cur_date))
				{
					return false;
				}
			}

			$this->shift_pattern->dept_id = $this->status[$date]->dept_id;

			if(!$this->shift_pattern->statusAtDate($cur_date))
			{
				$this->error = $this->shift_pattern->error;
				return false;
			}

			$this->status[$date]->shift_pattern_id = $this->shift_pattern->id;

			$cur_date->add($one_day);
		}

		return $this;		
	}

	public function getContractDetails($start, $end = false)
	{
		$cur_date = clone $start;

		if(!$end)
		{
			$end = $start;
		}

		$one_day = new DateInterval("P1D"); //1 Day interval;

		if(!$this->contract_details)
		{
			if(!$this->contract_details = new contract($this->link))
			{
				$this->error = $this->contract->error;
				return false;
			}
		}

		$this->contract_details->empid = $this->id;

		while($cur_date <= $end)
		{
			if(!$this->contract_details->statusAtDate($cur_date))
			{
				$this->error = $this->contract_details->error;
				return false;
			}

			$this->status[$cur_date->format("Y-m-d")]->contract = $this->contract_details->toObj();

			$cur_date->add($one_day);
		}

		return $this;
	}

}