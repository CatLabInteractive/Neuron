<?php


namespace Neuron\Exceptions;

use Neuron\Core\Error;


class OutputAlreadyStarted
	extends \Exception
{
	private $output;

	public function __construct ($output)
	{
		$this->output = $output;
	}
}