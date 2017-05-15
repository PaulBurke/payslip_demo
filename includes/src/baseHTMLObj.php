<?php

class baseHTMLObj
{
	public $id;
	public $class;
	protected $config_string;
	protected $content_string = "";

	protected $content = [];


	public function render()
	{
		if($this->id)
		{
			$id = "id='$this->id'";
		}else{
			$id = "";
		}

		if($this->class)
		{
			$class = "class='$this->class'";
		}else{
			$class = "";
		}

		$this->config_string = trim("$id $class");


		foreach($this->content as $c)
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
	}

}