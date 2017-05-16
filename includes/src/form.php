<?php

class form extends baseHTMLObj
{
	public function render()
	{
		parent::render();

		$form = "<form $this->config_string>$this->content_string</form>";

		return $form;
	}
}