<?php

class attendance
{
	public $shift_pattern;
	public $attendance_records;
	public $empid;
	public $cur_date;

	public $shift_start;
	public $lunch_start;
	public $lunch_end;
	public $shift_end;
	public $absent;
	public $hours;
	public $late;

	protected $attendance;

	protected $time_types = ['day_start', 'lunch_start', 'lunch_end', 'day_end'];

	public $error;

	protected $timezone;

	public function __construct()
	{
		$this->timezone = system_constants::getTimezone();
	}

	private function timeToInterval($time)
	{
		$time_codes = ['H', 'M', 'S'];

		$time_arr = explode(":", $time);

		$interval = "PT";

		for($i=0;$i<count($time_codes);$i++)
		{
			$v = intval($time_arr[$i]);

			if($v < 1)
			{
				continue;
			}

			$interval .= $v.$time_codes[$i];
		}

		return new DateInterval($interval);
	}

	public function getAttendance($date = false)
	{

		if(!$date)
		{
			$date = new DateTime("now", $this->timezone);
		}

		$this->cur_date = $date->format("Y-m-d");

		if(!$this->attendance)
		{
			if(!$this->attendance = new attendance_record)
			{
				$this->error = $this->attendance->error;
				return false;
			}
		}

		$this->attendance->empid = $this->empid;

		$day_start = $this->shift_pattern->day_start;
		$lunch_start = $this->shift_pattern->lunch_start;
		$lunch_duration = $this->shift_pattern->lunch_duration;
		$day_end = $this->shift_pattern->day_end;
		$sign_in_cut_off = $this->shift_pattern->sign_in_cut_off;


		$day_start_obj = new DateTime($this->cur_date." ".$day_start, $this->timezone);
		$lunch_start_obj = new DateTime($this->cur_date." ".$lunch_start, $this->timezone);
		$day_end_obj = new DateTime($this->cur_date." ".$day_end, $this->timezone);

		$lunch_interval = $this->timeToInterval($lunch_duration);

		$lunch_end_obj = clone $lunch_start_obj;
		$lunch_end_obj->add($lunch_interval);

		if($sign_in_cut_off)
		{
			$sign_in_cut_off_interval = $this->timeToInterval($sign_in_cut_off);

			$sign_in_cut_off = clone $day_start_obj;
			$sign_in_cut_off->add($sign_in_cut_off_interval);
		}

		$one_day = new DateInterval("P1D");

		if($day_start_obj > $day_end_obj)
		{
			// This would be the case when someone is on a night shift for example.
			$day_start_obj->sub($one_day);

			// The possibility of lunch being after day end is only possible when day start was after day end
			if($lunch_start_obj > $day_end_obj)
			{
				$lunch_start_obj->sub($one_day);
				$lunch_end_obj->sub($one_day); //If lunch start was greater then lunch end will definitely be greater.
			}else if($lunch_end_obj > $day_end_obj){
				// Even if the start of lunch wasn't after there's still a possibility that lunch end is after.
				$lunch_end_obj->sub($one_day);
			}
		}

		// These objects are for padding either side of the shift timings to try and ensure all relevant records are captured.

		$outside_diff = round( (86400 - ($day_end_obj->getTimestamp() - $day_start_obj->getTimestamp()))/2 );
		$mid_diff = round( ($day_end_obj->getTimestamp() - $day_start_obj->getTimestamp())/2 );

		$outside_diff_interval = new DateInterval("PT".$outside_diff."S");
		$mid_diff_interval = new DateInterval("PT".$mid_diff."S");

		// Lower limit is designed to get a point halfway between the end of the previous days shift time and the beginning of todays shift time.
		$lower_limit = clone $day_end_obj;
		$lower_limit->add($outside_diff_interval);
		$lower_limit->sub($one_day);
		

		// Upper limit is essentially the same but in reverse to try to capture the last relevant record of the day.
		$upper_limit = clone $day_start_obj;
		$upper_limit->add($one_day);
		$upper_limit->sub($outside_diff_interval);


		/*
			In the case of day_start, lunch_end and lunch_start we're looking for the closest record to the known
			shift time. For example, if a person's shift starts at 07:00 and they accidentaly clock in at multiple times,
			06:45, 06:55 and 07:10 then 06:50 is considered to be the actual record.

			This is not the case when clocking out for the day end when the last record is considered.

			To achieve this all records captured are compared in terms of time difference against the set shift time. These
			records are then sorted according to proximity and the best matching record given the above constraints is chosen.
		*/

		$time_diff_obj = new stdClass;
		$time_diff_obj->id = NULL;
		$time_diff_obj->val = NULL;

		$times = new stdClass;

		// Attendance_obj is going to hold the final details for each of the shift records
		// It'll be used to store relevant records and compare new records to see which is the better fit
		// In the attendance records while loop below.
		$attendance_obj = new stdClass;
		$attendance_obj->timestamp = NULL;	// Timestamp of the record time.
		$attendance_obj->time = NULL; 		// Timestring of the record time.
		$attendance_obj->time_obj = NULL; 	// DateTime obj
		$attendance_obj->diff = 86400; 		// Distance from this to the nominal record time.
		$attendance_obj->set = false;  		// Determines if a relevant record was found
		$attendance_obj->default = false;	// Default to use if no record exists.

		$attendance_times = new stdClass;
		$attendance_times->no_records = false;
		$attendance_times->hours = 0;
		$attendance_times->sign_in_cut_off = $sign_in_cut_off;
		$attendance_times->comment = NULL;
		$attendance_times->late = false;

		foreach($this->time_types as $tt)
		{
			$times->{$tt} = ${$tt."_obj"}->getTimestamp();
			$attendance_times->{$tt} = clone $attendance_obj;
			$attendance_times->{$tt}->default = ${$tt."_obj"};
		}

		$attendance_times->day_end->diff = 0;
		$this->attendance_records = $attendance_times;

		$upper_limit_str = $upper_limit->format("Y-m-d H:i:s");
		$lower_limit_str = $lower_limit->format("Y-m-d H:i:s");

		if(!$attendance_records = $this->attendance->getAll($this->empid, $lower_limit, $upper_limit))
		{
			if($this->attendance->error->fatal)
			{
				$this->error = $this->attendance->error;
				return false;	
			}else{
				$this->attendance_records->no_records = true;
				return $this->attendance_records;
			}			
		}

		while($attendance_records->fetch())
		{
			$time_diffs = [];

			foreach($this->time_types as $tt)
			{
				$time_diff = clone $time_diff_obj;
				$time_diff->id = $tt;
				$time_diff->val = abs($times->{$tt} - $this->attendance->ux_timestamp);
				$time_diffs[] = $time_diff;
			}

			usort($time_diffs, "timestampDistanceSort");

			if($time_diffs[0]->id == "day_end")
			{
				// Checking if the current record is further away than the existing record
				// and if it's greater than the existing record.
				if($time_diffs[0]->val > $attendance_times->day_end->diff
					&& $this->attendance->ux_timestamp > $attendance_times->day_end->timestamp)
				{
					$attendance_times->day_end->diff = $time_diffs[0]->val;
					$attendance_times->day_end->time = $this->attendance->timestamp;
					$attendance_times->day_end->timestamp = $this->attendance->ux_timestamp;
					$attendance_times->day_end->set = true;
					$attendance_times->day_end->time_obj = new DateTime($this->attendance->timestamp, $this->timezone);
				}
			}else{
				if($time_diffs[0]->val < $attendance_times->{$time_diffs[0]->id}->diff)
				{
					$attendance_times->{$time_diffs[0]->id}->diff = $time_diffs[0]->val;
					$attendance_times->{$time_diffs[0]->id}->time = $this->attendance->timestamp;
					$attendance_times->{$time_diffs[0]->id}->timestamp = $this->attendance->ux_timestamp;
					$attendance_times->{$time_diffs[0]->id}->set = true;
					$attendance_times->{$time_diffs[0]->id}->time_obj = new DateTime($this->attendance->timestamp, $this->timezone);
				}
			}
		}

		if($attendance_times->sign_in_cut_off && (!$attendance_times->day_start->time_obj || $attendance_times->sign_in_cut_off < $attendance_times->day_start->time_obj))
		{
			$attendance_times->late = true;
		}

		return $this->checkAttendance();
	}

	function checkAttendance($fill_blanks = false)
	{
		if($this->attendance_records->no_records)
		{
			return $this;
		}

		if($fill_blanks && !($this->shift_pattern->day_off || $this->shift_pattern->holiday))
		{
			// We're not going to set a default value for work on a day off or holiday as there's
			// no guarantee the person will be working a standard shift that day.
			foreach($this->time_types as $tt)
			{
				if(!$this->attendance_records->{$tt}->time)
				{
					$this->attendance_records->{$tt}->time_obj = $this->attendance_records->{$tt}->default;
				}
			}
		}

		$default_lunch_length = 3600;
		$default_day_length = $this->attendance_records->day_end->default->getTimestamp() - $this->attendance_records->day_start->default->getTimestamp();

		$default_lunch_after = round(($default_day_length - $default_lunch_length)/2); // It's assumed that Lunch will occur during the middle of the normal shift.

		$day_length = 0;
		$lunch_length = 0;

		if(!$this->attendance_records->day_start->time_obj && $this->attendance_records->lunch_start->time_obj && !$this->attendance_records->lunch_end->time_obj)
		{
			$this->attendance_records->day_start->time_obj = $this->attendance_records->lunch_start->time_obj;
			$this->attendance_records->lunch_start->time_obj = NULL;
		}

		if(!$this->attendance_records->day_end->time_obj && $this->attendance_records->lunch_end->time_obj && !$this->attendance_records->lunch_start->time_obj)
		{
			$this->attendance_records->day_end->time_obj = $this->attendance_records->lunch_end->time_obj;
			$this->attendance_records->lunch_end->time_obj = NULL;
		}

		if(!$this->attendance_records->lunch_start->time_obj || !$this->attendance_records->lunch_end->time_obj)
		{
			if(!$this->shift_pattern->lunch_duration)
			{
				$lunch_duration = "01:00:00";
			}else{
				$lunch_duration = $this->shift_pattern->lunch_duration;
			}

			$lunch_interval = $this->timeToInterval($lunch_duration);
			$lunch_length = timeToSeconds($lunch_duration);
		}else{
			$lunch_length = $this->attendance_records->lunch_end->time_obj->getTimestamp() - $this->attendance_records->lunch_start->time_obj->getTimestamp();
		}

		if($this->attendance_records->day_start->time_obj && $this->attendance_records->day_end->time_obj)
		{
			$day_length = $this->attendance_records->day_end->time_obj->getTimestamp() - $this->attendance_records->day_start->time_obj->getTimestamp();
		
		}else if($this->attendance_records->day_start->time_obj && $this->attendance_records->lunch_start->time_obj){

			$day_length = $this->attendance_records->lunch_start->time_obj->getTimestamp() - $this->attendance_records->day_start->time_obj->getTimestamp();

		}else if($this->attendance_records->lunch_end->time_obj && $this->attendance_records->day_end->time_obj){

			$day_length = $this->attendance_records->day_end->time_obj->getTimestamp() - $this->attendance_records->lunch_end->time_obj->getTimestamp();

		}

		if($day_length > 0)
		{
			if($day_length > ($default_lunch_after + $lunch_length))
			{
				$day_length = max($default_lunch_after, $day_length - $lunch_length);
			}

			$hours = $day_length/60/60;

			$minutes = round(($hours - floor($hours))*60/15)/4;

			$this->attendance_records->hours = floor($hours) + $minutes;
		}

		return $this->attendance_records;

	}
}

function timeToSeconds($str, $format = "H:M:S")
{
	$times = explode(":",$str);
	$formats = explode(":",strtolower($format));

	if(count($times)!=count($formats))
	{
		return false;
	}

	$multiples = [
				'h' => 3600,
				'm' => 60,
				's' => 1
				];

	$time = 0;

	for($i=0;$i<count($formats);$i++)
	{
		$time += intval($times[$i])*$multiples[$formats[$i]];
	}

	return $time;
}



function timestampDistanceSort($a, $b)
{
	if($a->val == $b->val)
	{
		return 0;
	}

	return ($a->val < $b->val) ? -1:1;
}