<?php

class contract extends dbStatusObj
{
	public $id;
	public $empid;
	public $basic;
	public $basic_currency_id;
	public $basic_currency;
	public $basic_currency_code;
	public $basic_currency_symbol;
	public $basic_currency_decimal_places;
	public $basic_recurrence;
	public $basic_recurrence_id;
	public $base_hours;
	public $base_hours_recurrence;
	public $base_hours_recurrence_id;
	public $workdays;
	public $ot_eligible;
	public $ot_multiplier;
	public $paid_leave;
	public $position_id;
	public $position;
	public $soc;
	public $eoc;

	protected $obj_values = ['id', 'basic', 'basic_currency_id','basic_currency', 'basic_currency_code', 'basic_currency_symbol', 'basic_currency_decimal_places', 'basic_recurrence',
							'basic_recurrence_id', 'base_hours', 'base_hours_recurrence', 'workdays', 'base_hours_recurrence_id', 'ot_eligible', 'ot_multiplier', 'paid_leave', 'position_id', 'position',
							'soc', 'eoc'];

	public function statusAtDate($date = false)
	{
		$this->date = $this->soc;

		if(parent::statusAtDate($date))
		{
			return true;
		}

		if(!isset($this->stmt_status_at_date))
		{
			$this->stmt_status_at_date = $this->link->prepare("
			SELECT a.`id`, a.`basic`, a.`basic_currency`, a.`basic_recurrence`, a.`base_hours`,
					a.`base_hours_recurrence`, a.`workdays`, a.`ot_eligible`, a.`ot_multiplier`, a.`paid_leave`, a.`position`, a.`soc`, a.`eoc`,
					b.`name`, b.`code`, b.`symbol`, b.`decimal_places`, c.`name`, d.`name`, e.`name`
			FROM `contracts` a
				LEFT JOIN
					`currencies` b
				ON
					a.`basic_currency` = b.`id`
				LEFT JOIN
					`recurrences` c
				ON
					a.`basic_recurrence` = c.`id`
				LEFT JOIN
					`recurrences` d
				ON
					a.`base_hours_recurrence` = d.`id`
				LEFT JOIN
					`positions` e
				ON
					e.`id` = a.`position`
			WHERE `emp_id` = ? && `soc` <= ? && `eoc` >= ?
			ORDER BY `soc` DESC LIMIT 1");

			if(!$this->stmt_status_at_date)
			{
				$this->error = new errorAlert("ool1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
				return false;
			}

			$this->stmt_status_at_date->bind_param("iss", $this->empid, $this->cur_date, $this->cur_date);
			$this->stmt_status_at_date->bind_result($this->id, $this->basic, $this->basic_currency_id, $this->basic_recurrence_id, $this->base_hours, $this->base_hours_recurrence_id, $this->workdays,
				$this->ot_eligible, $this->ot_multiplier, $this->paid_leave, $this->position_id, $this->soc, $this->eoc, $this->basic_currency, $this->basic_currency_code,
				$this->basic_currency_symbol, $this->basic_currency_decimal_places, $this->basic_recurrence, $this->base_hours_recurrence, $this->position);
		}

		return parent::getStatusAtDate("soc");
	}
}