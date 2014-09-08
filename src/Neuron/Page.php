<?php


namespace Neuron;

use Neuron\Core\Template;


class Page
{
	protected $title;
	protected $content;
	
	public function __construct ()
	{

	}

	public function setContent ($content)
	{
		$this->content = $content;
	}

	public function setTitle ($title)
	{
		$this->title = $title;
	}

	public function getOutput ()
	{
		$html = new Template ();

		$html->set ('title', $this->title);
		$html->set ('content', $this->content);

		return $html->parse ('index.phpt');
	}
}