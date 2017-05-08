<?php

// Basic database interaction object. All other database interactive objects will inherit from here.

class dbObj
{
	protected $link;
	protected $stmt_read;

	public $error;

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
			$this->error = new errorAlert("db_read_1", $this->link->error, $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->stmt_read->store_result();

		if($this->stmt_read->num_rows < 1)
		{
			$this->error = new errorAlert("db_read_2", "No records found.", $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		$this->stmt_read->fetch();

		return true;
	}
}