<?php

namespace Neuron\Tests;

/**
 * The part of mysqli the connect step uses.
 */
class FakeMysqliConnection
{
	/** @var string[] */
	public $charsets = array ();

	/** @var bool */
	public $acceptCharset = true;

	/** @var string */
	public $error = '';

	/** @var int */
	public $errno = 0;

	public function set_charset ($charset)
	{
		$this->charsets[] = $charset;
		return $this->acceptCharset;
	}
}
