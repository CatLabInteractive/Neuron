<?php


namespace Neuron\Exceptions;

use Neuron\Core\Error;


class OutputAlreadyStarted
	extends Error
{
	private $output;

	public function __construct ($output)
	{
		$this->output = $output;
	}
}
?>
