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
	private $shift_pattern_detail;
	private $contract_details;
	private $attendance_base;

	private $attendance_times = [];

	/*
	Methods:
	__construct($id = false, &$link = false) 	| Gets basic details if given an employee ID
	get($id) 									| Calls the read function
	read()										| Generates the prepared statement if not yet ready and reads info from database
	getStatus($start, $end = false)				| Daily status details are used by all the subsequent daily details
	getShiftPatterns($start, $end = false)		| Shift patterns are used by attendance and payroll
	getContractDetails($start, $end = false)	| Contract details are used by payroll
	getAttendance($start, $end = false)			| Attendance is used by payroll
	getPayroll($start, $end = false)			| Pulls together all the above functions to generate a payroll value
	checkLastWeekAttendance($date = false)		| Checks to see if the employee has attendance for the previous workdays.

	If the date values are known in advance then it's quicker to call the individual functions with the full date range rather than letting the
	subsequent functions call individual days as they can't find them.
	*/

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

	public function setID($id)
	{
		if(!intCheck::test($id))
		{
			$this->error = new errorAlert("emp", "$id is not a valid employee ID.\nAn employee ID should contain only numbers.");
			return false;
		}

		$this->status = [];

		return $this->get($id);
	}

	public function get($id)
	{
		$this->id = $id;
		return $this->read();
	}

	public function read()
	{
		if(!intCheck::test($this->id))
		{
			$this->error = new errorAlert("emp0", "$this->id is not a valid employee ID.\nAn employee ID should only contain numbers",
										$_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

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

	public function readAll()
	{
		if(!$this->stmt_read_all)
		{
			$this->stmt_read_all = $this->link->prepare("SELECT `id`, `name`, `email` FROM `employees` ORDER BY `name`");

			if(!$this->stmt_read_all)
			{
				$this->error = new errorAlert("emp2", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_read_all->bind_result($this->id, $this->name, $this->email);
		}

		return parent::readAll();
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

		if(!$this->shift_pattern_detail)
		{
			if(!$this->shift_pattern_detail = new shift_pattern_detail($this->link))
			{
				$this->error = $this->shift_pattern_detail->error;
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

			if(!$this->shift_pattern_detail->get($this->shift_pattern->id, $cur_date))
			{
				$this->error = $this->shift_pattern_detail->error;
				return false;
			}

			$this->status[$date]->shift_pattern = $this->shift_pattern_detail->toObj();
			$this->status[$date]->shift_pattern->workdays = $this->shift_pattern->workdays;
			$this->status[$date]->shift_pattern->workdays_in_month = $this->shift_pattern->workdaysInMonth($cur_date);

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

	public function getAttendance($start, $end = false)
	{
		$cur_date = clone $start;

		if(!$end)
		{
			$end = $start;
		}

		$one_day = new DateInterval("P1D"); //1 Day interval;

		if(!$this->attendance_base)
		{
			$this->attendance_base = new attendance;	
		}

		$this->attendance_base->empid = $this->id;

		while($cur_date <= $end)
		{
			$date = $cur_date->format("Y-m-d");

			if(!isset($this->status[$date]->shift_pattern))
			{
				if(!$this->getShiftPatterns($cur_date))
				{
					return false;
				}
			}

			$this->attendance_base->shift_pattern = $this->status[$date]->shift_pattern;

			if(!$attendance_times = $this->attendance_base->getAttendance($cur_date))
			{
				$this->error = $this->attendance_base->error;
				return false;
			}

			$this->status[$date]->attendance = $attendance_times;

			$cur_date->add($one_day);
		}

		return $this;

	}

	public function getPayroll($start, $end = false)
	{
		$cur_date = clone $start;

		if(!$end)
		{
			$end = $start;
		}

		$one_day = new DateInterval("P1D"); //1 Day interval;

		$weekday = $start->format("N");

		$week_day_start = system_constants::$week_day_start;

		/* 	
		We're going to backtrack by up to a week to the previous work week to get any past attendance for the upcoming weeked
		and for calculating overtime for the weekly roll-up.

		[Need to put something here for calculating monthly overtime should the start day not be the beginning of the month]
		*/

		$weekly_rollup_hours = 0;
		$monthly_rollup_hours = 0;
		$attendance = 0; // Check to see if the employee has been in attendance this week to determine if the weekend should be paid.

		$days = $weekday - $week_day_start;

		if($days < 1)
		{
			// Rolls the day back around when outside bounds of 1-7
			$days += 7;
		}

		$interval = new DateInterval("P".($days)."D");

		$previous_period_start = clone $start;
		$previous_period_start->sub($interval);
		$previous_period_end = clone $start;
		$previous_period_end->sub($one_day);

		if(!$this->getAttendance($previous_period_start, $previous_period_end))
		{
			return false;
		}

		while($previous_period_start <= $previous_period_end)
		{
			$date = $previous_period_start->format("Y-m-d");

			$weekly_rollup_hours += $this->status[$date]->attendance->hours;
			
			if(!$this->status[$date]->attendance->no_records)
			{
				$attendance += 1;
			}

			$previous_period_start->add($one_day);
		}

		$currency_obj = new stdClass;
		$currency_obj->value = 0;

		$currency_values = ['code', 'symbol', 'decimal_places'];

		foreach($currency_values as $cv)
		{
			$currency_obj->{$cv} = NULL;
		}

		$payroll_obj = new stdClass;

		$payroll_obj->ot = 0;
		$payroll_obj->st = 0;
		$payroll_obj->st_value = NULL;
		$payroll_obj->ot_value = NULL;
		$payroll_obj->day_value = NULL;  // This is used for days off, holiday days or on leave days.

		while($cur_date <= $end)
		{
			if($cur_date->format("N") == $week_day_start)
			{
				$attendance = 0;
				$weekly_rollup_hours = 0;
			}

			if(!isset($this->status[$date]->attendance))
			{
				if(!$this->getAttendance($cur_date))
				{
					return false;
				}
			}

			if(!isset($this->status[$date]->contract))
			{
				if(!$this->getContractDetails($cur_date))
				{
					return false;
				}
			}

			$date = $cur_date->format("Y-m-d");
			$days_in_month = intval($cur_date->format("t"));


			$payroll_value = clone $payroll_obj;
			$this->status[$date]->payroll = $payroll_value;

			if($this->status[$date]->contract->workdays)
			{
				/*
					For overtime purposes it's required to know the period over which the contractual hours should be worked.
					If that period is not specified in the contract terms then it'll be necessary to figure it out from the 
					days worked in the shift pattern.
				*/

				$workdays = $this->status[$date]->contract->workdays;

			}else{
				$workdays = $this->status[$date]->shift_pattern->workdays;
			}

			if($this->status[$date]->shift_pattern->holiday)
			{
				$payroll_value->ot = $this->status[$date]->attendance->hours;
				$payroll_value->st = 0;
			}else{

				switch($this->status[$date]->contract->base_hours_recurrence)
				{
					// This is to get the basic hours for a single work day based on the contractual work hours.
					// Overtime will represent an average work day.

					case "Day":
						$base_hours_multiple = 1;
						$overtime_base_hours = 1;

						if($this->status[$date]->contract->ot_eligible && $this->status[$date]->attendance->hours > 0)
						{
							if($this->status[$date]->shift_pattern->day_off)
							{
								$payroll_value->ot = $this->status[$date]->attendance->hours;
							}else{
								$payroll_value->ot = max(0, $this->status[$date]->attendance->hours - $this->status[$date]->contract->base_hours);
								$payroll_value->st = $this->status[$date]->attendance->hours - $payroll_value->ot;
							}

						}else{
							$payroll_value->st = $this->status[$date]->attendance->hours;
						}

						break;

					case "Week":
						$base_hours_multiple = 1/$workdays;
						$overtime_base_hours = 1/$workdays;


						$weekly_rollup_hours += $this->status[$date]->attendance->hours;

						if($this->status[$date]->contract->ot_eligible)
						{
							$payroll_value->ot = max(0, $weekly_rollup_hours - $this->status[$date]->contract->base_hours);
							$payroll_value->st = $this->status[$date]->attendance->hours - $payroll_value->ot;

							$weekly_rollup_hours = min($weekly_rollup_hours,$this->status[$date]->contract->base_hours);
						}else{

							$payroll_value->st = $this->status[$date]->attendance->hours;

						}


						break;

					case "Month":
						$base_hours_multiple = 1/$this->status[$date]->shift_pattern->workdays_in_month;
						$overtime_base_hours = 1/($workdays/7*365/12);

						$monthly_rollup_hours += $this->status[$date]->attendance->hours;

						if($this->status[$date]->contract->ot_eligible)
						{
							$payroll_value->ot = min(0, $monthly_rollup_hours - $this->status[$date]->contract->base_hours);
							$payroll_value->st = $this->status[$date]->attendance->hours - $payroll_value->st;

							$monthly_rollup_hours = $this->status[$date]->contract->base_hours;
						}else{

							$payroll_value->st = $this->status[$date]->attendance->hours;
							
						}

						break;

					default:
						$this->error = new errorAlert("pay1", "Invalid base hours recurrence specified", $_SERVER['PHP_SELF'],__LINE__);
						return false;
				}
			}

			$base_hours = $this->status[$date]->contract->base_hours * $base_hours_multiple;

			switch($this->status[$date]->contract->basic_recurrence)
			// With this we're going t calculate a basic daily value.
			// We'll need to calculate an average daily basic value for use with overtime.
			{
				case "Day":
					$daily_basic_multiple = 1;
					$overtime_basic_multiple = 1;
					break;

				case "Week":
					$daily_basic_multiple = 1/$workdays;
					$overtime_basic_multiple = 1/$workdays;
					break;

				case "Month":
					$daily_basic_multiple = 1/$days_in_month;
					$overtime_basic_multiple = 1/(365/12);
					break;

				default:
					$this->error = new errorAlert("pay2", "Invalid basic salary recurrence specified", $_SERVER['PHP_SELF'],__LINE__);
					return false;
			}

			$daily_basic = $this->status[$date]->contract->basic * $daily_basic_multiple;
			$base_hours = $this->status[$date]->contract->base_hours * $base_hours_multiple;
			$hourly_rate = $daily_basic/$base_hours;

			$overtime_rate = $this->status[$date]->contract->basic * $overtime_basic_multiple/($this->status[$date]->contract->base_hours*$overtime_base_hours);


			$currency_code = $this->status[$date]->contract->basic_currency_code;

			if(!isset(${"currency_$currency_code"}))
			{
				${"currency_$currency_code"} = clone $currency_obj;

				foreach($currency_values as $cv)
				{
					${"currency_$currency_code"}->{$cv} = $this->status[$date]->contract->{"basic_currency_$cv"};
				}
			}

			$payroll_value->day_value = clone ${"currency_$currency_code"};

			if($this->status[$date]->attendance->no_records)
			{
				if($this->status[$date]->shift_pattern->day_off && ($attendance || $this->checkLastWeekAttendance($cur_date)))
				{
					// If there was attendance during the week then pay the weekend.

					$payroll_value->day_value->value = $daily_basic;
					$this->status[$date]->attendance->comment = "Day Off";

				}else if($this->status[$date]->shift_pattern->holiday){

					$payroll_value->day_value = clone ${"currency_$currency_code"};
					$payroll_value->day_value->value = $daily_basic;
					$this->status[$date]->attendance->comment = $this->status[$date]->shift_pattern->holiday_name;

				}else if($this->status[$date]->on_leave){

					if($this->status[$date]->contract->paid_leave)
					{
						$payroll_value->day_value = clone ${"currency_$currency_code"};
						$payroll_value->day_value->value = $daily_basic;
						$this->status[$date]->attendance->comment = "Paid Leave";
					}else{
						$this->status[$date]->attendance->comment = "Unpaid Leave";
					}

				}else{

					$this->status[$date]->attendance->comment = "Absent";
				}
			}else{		

				$payroll_value->day_value = clone ${"currency_$currency_code"};
				$payroll_value->day_value->value = $daily_basic;

				if($this->status[$date]->shift_pattern->day_off)
				{
					$this->status[$date]->attendance->comment = "Day Off";
				}else if($this->status[$date]->shift_pattern->holiday){
					$this->status[$date]->attendance->comment = $this->status[$date]->shift_pattern->holiday_name;
				}

				if($this->status[$date]->contract->ot_eligible || $this->status[$date]->shift_pattern->holiday)
				{
					$payroll_value->ot_value = clone ${"currency_$currency_code"};
					$payroll_value->ot_value->value = $payroll_value->ot * $overtime_rate * $this->status[$date]->contract->ot_multiplier;
				}
			}

			if(!$this->status[$date]->shift_pattern->day_off && $this->status[$date]->attendance->late)
			{
				if(!$this->status[$date]->attendance->comment)
				{
					$this->status[$date]->attendance->comment = "Late";
				}else{
					$this->status[$date]->attendance->comment = "Late - ".$this->status[$date]->attendance->comment;
				}
			}

			if(!$this->status[$date]->attendance->no_records)
			{
				$attendance += 1;
			}


			$cur_date->add($one_day);
		}

		return $this;
	}

	public function checkLastWeekAttendance($date = false)
	{
		if(!$date)
		{
			$date = new date("now", system_constants::getTimezone());
		}else{
			$date = clone $date;
		}

		$one_day = new DateInterval("P1D");

		$attendance = 0;

		// We're only going to do six days, no need to check today as the only reason to call this is if
		// today had no attendance

		$date->sub($one_day);

		if($this->status[$date->format("Y-m-d")]->shift_pattern->day_off)
		{
			$break = 1;
		}else{
			$break = 0;
		}

		for($i=0;$i<6;$i++)
		{
			$dv = $date->format("Y-m-d");

			if(!isset($this->status[$dv]->attendance) || ($i > $break && $this->status[$dv]->shift_pattern->day_off))
			{ // We've hit the previous weekend and don't want to count any prior attendance or there's no more records to check
				break;
			}

			if(!$this->status[$dv]->attendance->no_records)
			{
				$attendance++;
			}

			$date->sub($one_day);
		}

		return $attendance;
	}

}