<?php
class select_option
{
	public $value;
	public $text;

	public function __construct($value, $text)
	{
		$this->value = $value;
		$this->text = htmlspecialchars($text);
	}

	public function render()
	{
		return "<option value='$this->value'>$this->text</option>";
	}
}