<?php

class select extends baseHTMLObj
{
	public function addOption($value, $text)
	{
		$this->content[] = new select_option($value, $text);
	}

	public function addSpacer()
	{
		$spacer = new select_option(-1,"──────────");
		array_unshift($this->content, $spacer);
	}

	public function render()
	{
		parent::render();

		$select = "<select $this->config_string> $this->content_string </select>";

		return $select;
	}
}