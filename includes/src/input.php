<?php

class input extends baseHTMLObj
{

	public function render()
	{
		parent::render();

		$input = "<input $this->config_string />";

		return $this->checkLabel($input);
	}
		
}

