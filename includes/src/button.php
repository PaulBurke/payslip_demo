<?php
class button extends baseHTMLObj
{
	public function render()
	{
		parent::render();

		$button = "<button $this->config_string> $this->content_string </button>";

		return $button;
	}
}