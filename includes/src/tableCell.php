<?php

class tableCell extends baseHTMLObj
{
	public $head = false;

	public function __construct($content = false)
	{
		if($content)
		{
			$this->content[] = $content;
		}

		return $this;
	}

	public function render()
	{
		parent::render();

		if($this->head)
		{
			$type = "th";
		}else{
			$type = "td";
		}

		$cell = "<$type $this->config_string>$this->content_string</$type>";

		return $cell;
	}
}