<?php
class errorAlert
{
	public $id;
	public $message;
	public $script;
	public $line;
	public $log = true;
	public $fatal = true;

	public function json()
	{
		return json_encode($this->arr());
	}

	public function __construct($id, $message, $script, $line, $log = true)
	{
		$this->id = $id;
		$this->message = $message;
		$this->script = $script;
		$this->line = $line;
		$this->log = $log;

		return $this;
	}

	public function __destruct()
	{
		if($this->log)
		{
			error_log("Error $this->id || $this->message || Occured In $this->script on line $this->line");
		}
	}

	public function arr()
	{
		return ['error' => 1, 'message' => "Error No: $this->id || $this->message"];
	}
}