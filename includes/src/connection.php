<?php
class connection
{
	public $link;
	public $error;

	public function __construct($link = false)
	{
		if($link)
		{
			$this->link = $link;
			return $this;
		}

		$this->link = new mysqli(
								database_constants::$address,
								database_constants::$user,
								database_constants::$password,
								database_constants::$database,
								database_constants::$port);

		if($this->link->connect_errno)
		{
			$this->error = new  errorAlert(1, "Failed to connect to MySQL: (" . $this->link->connect_errno . ") " . $this->link->connect_error, $_SERVER['PHP_SELF'], __LINE__);
			error_log("test");
			return false;
		}

		if(!$this->link->set_charset(database_constants::$charset))
		{
			$this->error = new errorAlert(2, "Error loading character set ".database_constants::$charset.": ".$this->link->error, $_SERVER['PHP_SELF'],__LINE__);
			return false;
		}

		return $this;
	}
}