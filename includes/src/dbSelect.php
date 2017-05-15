<?php
class dbSelect extends select
{
	public $error;

	public function __construct($classname, $value_name = "value", $text_name = "text")
	{
		if(!$class = new $classname)
		{
			$this->error = $class->error;
			return false;
		}

		if(!$results = $class->readAll())
		{
			$this->error = $class->error;
			return false;
		}

		while($results->fetch())
		{
			$this->addOption($class->{$value_name}, $class->{$text_name});
		}

		return $this;
	}
}