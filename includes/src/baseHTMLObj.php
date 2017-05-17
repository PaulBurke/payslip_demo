<?php

class baseHTMLObj
{
	public $id;
	public $class;
	protected $config_string = "";
	protected $content_string = "";
	public $label = false;

	protected $content = [];
	protected $properties = [];
	protected $functions = [];

	public function addElement($var, $pos = false)
	{
		if($pos !== false)
		{
			$this->content = array_splice($this->content, $pos, 0, $var);
		}else{
			$this->content[] = $var;
		}

		return $var;
	}

	public function addContent($var)
	{
		$this->content[] = $var;
	}

	public function addFunction($event, $action, $vars=['this'])
	{
		$this->functions[] = [$event, $action, $vars];
	}

	protected function renderFunctions()
	{
		$functions = "";

		foreach($this->functions as $f)
		{
			$functions .= " ".$f[0]."='".$f[1]."(".implode(",",$f[2]).");'";
		}

		$this->config_string .= $functions;

		return $this;
	}

	public function addProperty($name, $value = false)
	{
		$this->properties[] = [$name, $value];
	}

	public function renderProperties()
	{
		foreach($this->properties as $p)
		{
			if($p[1])
			{
				$value = "='".$p[1]."'";
			}else{
				$value = "";
			}

			$this->config_string .= " ".$p[0].$value;
		}
	}

	protected function renderContent()
	{
		foreach($this->content as $c)
		{
			$type = gettype($c);

			switch ($type)
			{
				case "object":
					$this->content_string .= $c->render();
					break;

				default:
					$this->content_string .= $c;
			}
		}

		return $this;
	}

	public function render()
	{
		if($this->id)
		{
			$this->config_string .= "id='$this->id'";
		}

		if($this->class)
		{
			$this->config_string .= " class='$this->class'";
		}

		$this->renderProperties();

		$this->renderFunctions();

		$this->renderContent();

		return $this;
	}

	public function checkLabel($html)
	{
		if(!$this->label)
		{
			return $html;
		}

		$html = "
		<div class='form-group'>
			<label>$this->label</label>
			$html
		</div>";

		return $html;
	}
}