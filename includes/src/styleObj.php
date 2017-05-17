<?php

class styleObj
{
	public $styles = [];
	public $str;

	public function getStyle()
	{
		if(!$this->str)
		{
			$this->str = $this->render();
		}

		return $this->str;
	}

	public function render()
	{
		$str = "style='";

		foreach($this->styles as $style)
		{
			$str .= $style.":".$this->{$style}."; ";
		}

		$str .= "'";

		return $str;
	}

	public function add($style, $value)
	{
		$this->styles[] = $style;
		$this->{$style} = $value;
		$this->str = false;
		return $this;
	}

	public function remove($style)
	{
		if(property_exists($this, $style))
		{
			unset($this->styles[array_search($style, $this->styles)]);
			unset($this->{$style});
		}
		$this->str = false;
		return $this;
	}
}