<?php
require_once("autoloader.php");

function generatePayslip($empid, $start, $end = false)
{
	if(!intCheck::test($empid))
	{
		$error = new errorAlert("ps1", "$empid is not a valid value for employee ID.", $_SERVER['PHP_SELF'],__LINE__, false);
		print $error->json();
		exit;
	}

	if(!dateCheck::test($start))
	{
		$error = new errorAlert("ps2", "Invalid value for start date.", $_SERVER['PHP_SELF'],__LINE__, false);
		print $error->json();
		exit;
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
		print $employee->error->json();
		exit;
	}

	$timezone = system_constants::getTimezone();

	$cur_date = clone $start;

	$one_day = new DateInterval("P1D");

	print "Name: $employee->name <br><br>";

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

	$headings = ["Day","Date","Day Start", "Lunch Start", "Lunch End", "Day End", "Standard Time", "Overtime", "Total Hours", "Value", "Comment"];

	$table->addRow($headings, "thead");

	$time_types = ['day_start', 'lunch_start', 'lunch_end', 'day_end'];

	$pay_types = ['st_value', 'ot_value', 'day_value'];

	$total_pay = [];
	$day_pay = [];
	$total_st = 0;
	$total_ot = 0;

	while($cur_date <= $end)
	{
		$date = $cur_date->format("Y-m-d");
		$day = $cur_date->format("l");
		
		$arr = [$date, $day];

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
				$arr[] = $employee->status[$date]->payroll->{$t};
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


		$table->addRow($arr);
		$cur_date->add($one_day);
	}

	$total_hours = $total_st + $total_ot;

	$totals = ['total_st', 'total_ot', 'total_hours'];

	foreach($totals as $t)
	{
		if(${$t} == 0)
		{
			${$t} = "-";
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


	$arr = [NULL,NULL,NULL,NULL,NULL,NULL,$total_st, $total_ot, $total_hours, $total_value, NULL];

	$table->addRow($arr, "tfoot");

	print $table->render();

}