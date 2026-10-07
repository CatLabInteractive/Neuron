<?php

namespace Neuron\Tests;

use Neuron\Interfaces\Logger;

/**
 * Keeps the lines it is given, for assertions.
 */
class RecordingLogger implements Logger
{
	/** @var string[] */
	public $lines = array ();

	public function log ($string, $replace = false, $color = null)
	{
		$this->lines[] = $string;
	}
}
