<?php

// Basic database interaction object. All other database interactive objects will inherit from here.

class dbObj
{
	protected $link;
	protected $stmt_read;
	protected $stmt_read_all;

	public $error;

	protected $obj_values = [];

	protected $obj;

	public function __construct(&$link = false)
	{
		if(!$link)
		{
			if(!$connection = new connection)
			{
				$this->error = $this->link->error;
				return false;
			}

			$this->link = $connection->link;
		}else{
			$this->link = &$link;
		}

		return true;
	}

	public function read()
	{
		if(!$this->stmt_read->execute())
		{
			$this->error = new errorAlert("db_read_1", $this->stmt_read->error, $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->stmt_read->store_result();

		if($this->stmt_read->num_rows < 1)
		{
			$this->error = new errorAlert("db_read_2", "No records found.", $_SERVER['PHP_SELF'],__LINE__);
			$this->error->fatal = false;
			return false;
		}

		$this->stmt_read->fetch();

		return true;
	}

	public function readAll()
	{
		if(!$this->stmt_read_all->execute())
		{
			$this->error = new errorAlert("db_read_3", $this->stmt_read_all->error, $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->stmt_read_all->store_result();

		if($this->stmt_read_all->num_rows < 1)
		{
			$this->error = new errorAlert("db_read_4", "No records found.", $_SERVER['PHP_SELF'],__LINE__);
			$this->error->fatal = false;
			return false;
		}

		return $this->stmt_read_all;
	}

	public function toObj()
	{
		if(!$this->obj)
		{
			$this->obj = new stdClass;

			foreach($this->obj_values as $ov)
			{
				$this->obj->{$ov} = NULL;
			}
		}

		$obj = clone $this->obj;

		foreach($this->obj_values as $ov)
		{
			$obj->{$ov} = $this->{$ov};
		}

		return $obj;
	}
}