<?php

class table extends baseHTMLObj
{
	protected $header = [];
	protected $footer = [];

	public function addRow($content = [], $area = "tbody")
	{
		if($area == "thead")
		{
			$head = true;
		}else{
			$head = false;
		}

		$row = new tablerow($content, $head);

		$this->content[] = $row;

		return $row;
	}

	protected function renderContent()
	{
		$this->content = array_reverse($this->content);

		while($c = array_pop($this->content))
		{
			$type = gettype($c);

			switch ($type)
			{
				case "object":
					$this->content_string .= $c->render();
					break;

				case "string":
					$this->content_string .= $c;
					break;
			}
		}

		return $this;
	}

	public function render()
	{
		$this->content = array_merge($this->header, $this->content, $this->footer);

		parent::render();

		$table = "<table $this->config_string>$this->content_string</table>";

		return $table;
	}
}