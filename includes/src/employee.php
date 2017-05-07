<?php

// Class for handling employee details. Will pull information from the database base on supplied employee ID.

class employee extends dbObj
{
	public $id;
	public $name;
	public $email;

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
}