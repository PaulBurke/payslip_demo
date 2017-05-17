<?php

class table extends baseHTMLObj
{
	protected $thead = [];
	protected $tfoot = [];

	public function addRow($content = [], $area = "tbody")
	{
		if($area == "thead")
		{
			$head = true;
		}else{
			$head = false;
		}

		if($area == "tbody")
		{
			$area = "content";
		}

		$row = new tablerow($content, $head);

		$this->{$area}[] = $row;

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
		foreach(['thead', 'tfoot'] as $area)
		{

			if(count($this->{$area}) > 0)
			{
				${$area} = "<$area>";

				foreach($this->{$area} as $row)
				{
					${$area} .= $row->render();
				}

				${$area} .= "</$area>";
			}else{
				${$area} = "";
			}
		}

		parent::render();

		$table = "<table $this->config_string> $thead <tbody> $this->content_string </tbody> $tfoot </table>";

		return $table;
	}
}