<?php

class tablerow extends baseHTMLObj
{
	public $head = false;

	public function __construct($content = [], $head = false)
	{
		$this->head = $head;

		foreach($content as $c)
		{
			$this->addCell($c);
		}
	}

	public function addCell($content = false)
	{
		$cell = new tableCell($content);
		$this->content[] = $cell;

		if($this->head)
		{
			$cell->head = true;
		}


		return $cell;
	}

	public function render()
	{
		parent::render();

		$row = "<tr $this->config_string>$this->content_string</tr>";

		return $row;
	}

	public function setCellsStyle(&$obj)
	{
		foreach($this->content as $c)
		{
			$c->addStyleObj($obj);
		}

		return $this;
	}
}