<?php
require_once("vendor/autoload.php");

function generatePayslip($empid, $start, $end = false, $email = false)
{
	if(!intCheck::test($empid))
	{
		$error = new errorAlert("ps1", "$empid is not a valid value for employee ID.", $_SERVER['PHP_SELF'],__LINE__, false);
		return $error;
	}

	if(!dateCheck::test($start))
	{
		$error = new errorAlert("ps2", "Invalid value for start date.", $_SERVER['PHP_SELF'],__LINE__, false);
		return $error;
	}

	$start = new DateTime($start, system_constants::getTimezone());

	if(!$end || !dateCheck::test($end))
	{
		$end = clone $start;
		$days = intval($end->format("t")) - intval($end->format("j"));

		$end->add(new DateInterval("P".$days."D"));
	}else{
		$end = new DateTime($end, system_constants::getTimezone());
	}


	if(!$employee = new employee($empid))
	{
		$error =  $employee->error->json();
		return $error;
	}

	$timezone = system_constants::getTimezone();

	$cur_date = clone $start;

	$one_day = new DateInterval("P1D");

	if(!$employee->getStatus($start, $end))
	{
		print $employee->error->json;
		exit;
	}

	if(!$employee->getShiftPatterns($start, $end))
	{
		print $employee->error->json();
		exit;
	}

	if(!$employee->getContractDetails($start, $end))
	{
		print $employee->error->json();
		exit;
	}

	if(!$employee->getAttendance($start,$end))
	{
		print $employee->error->json();
		exit;
	}

	if(!$employee->getPayroll($start, $end))
	{
		print $employee->error->json();
		exit;
	}

	$table = new table;
	$table->class = "table table-striped table-hover";

	$headings = ["Day","Date","Department","Day Start", "Lunch Start", "Lunch End", "Day End", "Standard Time", "Overtime", "Total Hours", "Value", "Comment"];

	$table->addRow($headings, "thead");

	$time_types = ['day_start', 'lunch_start', 'lunch_end', 'day_end'];

	$pay_types = ['st_value', 'ot_value', 'day_value'];

	$total_pay = [];
	$day_pay = [];
	$total_st = 0;
	$total_ot = 0;

	$summary_table = new table;
	$summary_table->class = "table table-bordered table-striped table-hover";

	$summary_table->addRow(["Name:", $employee->name]);
	$summary_table->addRow(["Emp ID:", $employee->id]);

	$eoc_date = new DateTime($employee->status[$start->format("Y-m-d")]->contract->eoc, system_constants::getTimezone());
	$contract_date = clone $start;

	if($eoc_date < $end)
	{
		while($contract_date < $end)
		{
			$date = $contract_date->format("Y-m-d");

			$eoc_date = new DateTime($employee->status[$date]->contract->eoc, system_constants::getTimezone());

			$row = $summary_table->addRow();
			$cell = $row->addCell("Contract ".$employee->status[$date]->contract->soc." to ".$eoc_date->format("Y-m-d"));
			$cell->addProperty("colspan", 2);

			$summary_table->addRow(["Position:", $employee->status[$date]->contract->position]);
			$salary = $employee->status[$date]->contract->basic_currency_symbol." ".number_format($employee->status[$date]->contract->basic,0)." per ".$employee->status[$date]->contract->basic_recurrence;

			$summary_table->addRow(["Basic Salary:", $salary]);

			if($employee->status[$date]->contract->ot_eligible)
			{
				$overtime_after = $employee->status[$date]->contract->base_hours." hours per ".$employee->status[$date]->contract->base_hours_recurrence;
				$summary_table->addRow(["Overtime After:", $overtime_after]);
			}

			$contract_date = clone $eoc_date;
			$contract_date->add($one_day);
		}
	}else{

		$date = $start->format("Y-m-d");

		$summary_table->addRow(["Position:", $employee->status[$date]->contract->position]);
		$salary = $employee->status[$date]->contract->basic_currency_symbol." ".number_format($employee->status[$date]->contract->basic,0)." per ".$employee->status[$date]->contract->basic_recurrence;

		$summary_table->addRow(["Basic Salary:", $salary]);

		if($employee->status[$date]->contract->ot_eligible)
		{
			$overtime_after = $employee->status[$date]->contract->base_hours." hours per ".$employee->status[$date]->contract->base_hours_recurrence;
			$summary_table->addRow(["Overtime After:", $overtime_after]);
		}
	}


	while($cur_date <= $end)
	{
		$date = $cur_date->format("Y-m-d");
		$day = $cur_date->format("l");
		
		$arr = [$date, $day, $employee->status[$date]->dept_name];

		foreach($time_types as $tt)
		{
			if($employee->status[$date]->attendance->{$tt}->time_obj)
			{
				$time = $employee->status[$date]->attendance->{$tt}->time_obj->format("H:i");
			}else{
				$time = "-";
			}

			$arr[] = $time;
		}

		foreach(['st', 'ot'] as $t)
		{
			if($employee->status[$date]->payroll->{$t} == 0)
			{
				$arr[] = "-";
			}else{
				$arr[] = number_format($employee->status[$date]->payroll->{$t},2);
			}

			${"total_$t"} += $employee->status[$date]->payroll->{$t};
		}

		if($employee->status[$date]->attendance->hours == 0)
		{
			$arr[] = "-";
		}else{
			$arr[] = number_format($employee->status[$date]->attendance->hours,2);
		}

		$day_value = [];
		
		foreach($pay_types as $pt)
		{
			if($employee->status[$date]->payroll->{$pt})
			{
				if(!isset($total_pay[$employee->status[$date]->payroll->{$pt}->code]))
				{
					$total_pay[$employee->status[$date]->payroll->{$pt}->code] = clone $employee->status[$date]->payroll->{$pt};
				}else{
					$total_pay[$employee->status[$date]->payroll->{$pt}->code]->value += $employee->status[$date]->payroll->{$pt}->value;
				}

				if(!isset($day_value[$employee->status[$date]->payroll->{$pt}->code]))
				{
					$day_value[$employee->status[$date]->payroll->{$pt}->code] = clone $employee->status[$date]->payroll->{$pt};
				}else{
					$day_value[$employee->status[$date]->payroll->{$pt}->code]->value += $employee->status[$date]->payroll->{$pt}->value;
				}

			}
		}

		$str = "";

		foreach($day_value as $dv)
		{
			if($dv->value > 0)
			{
				$str .= $dv->symbol." ".number_format($dv->value, $dv->decimal_places)."<br>";	
			}				
		}

		if(strlen($str) > 0)
		{
			$arr[] = substr($str,0,-4);	
		}else{
			$arr[] = "-";
		}

		$arr[] = $employee->status[$date]->attendance->comment;


		$row = $table->addRow($arr);

		if($employee->status[$date]->shift_pattern->day_off || $employee->status[$date]->shift_pattern->holiday)
		{
			$row->class = "attendance-day-off";
		}else if($employee->status[$date]->on_leave){
			$row->class = "attendance-on-leave";
		}else if($employee->status[$date]->attendance->no_records){
			$row->class = "attendance-absent";
		}

		$cur_date->add($one_day);
	}

	$total_hours = $total_st + $total_ot;

	$totals = ['total_st', 'total_ot', 'total_hours'];

	foreach($totals as $t)
	{
		if(${$t} == 0)
		{
			${$t} = "-";
		}else{
			${$t} = number_format(${$t},2);
		}
	}


	$total_value = "";

	foreach($total_pay as $tp)
	{
		if($tp->value > 0)
		{
			$total_value .= $tp->symbol." ".number_format($tp->value, $tp->decimal_places)."<br>";
		}
	}

	if(strlen($total_value) > 0)
	{
		$total_value = substr($total_value,0,-4);
	}else{
		$total_value = "-";
	}


	$arr = [NULL,NULL,NULL,NULL,NULL,NULL,"Total:",$total_st, $total_ot, $total_hours, $total_value, NULL];

	$table->addRow($arr, "tfoot");

	$summary_table = "<div class='col-sm-6 col-xs-12'>".$summary_table->render()."</div>";

	return [
			'error' => 0,
			'result' => $summary_table.$table->render()
			];
}