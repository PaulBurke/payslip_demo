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
		return json_encode(['error' => true, 'message' => "Error No: $this->id || $this->message"]);
	}

	public function __construct($id, $message, $script, $line)
	{
		$this->id = $id;
		$this->message = $message;
		$this->script = $script;
		$this->line = $line;

		return $this;
	}

	public function __destruct()
	{
		if($this->log)
		{
			error_log("Error $this->id || $this->message || Occured In $this->script on line $this->line");
		}
	}
}