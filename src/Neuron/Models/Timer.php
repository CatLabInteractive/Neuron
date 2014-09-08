<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/09/14
 * Time: 21:12
 */

namespace Neuron\Models;


class Timer {

	private $start;
	private $process;

	public function __construct ($process)
	{
		$this->start = microtime(true);
		$this->process = $process;
	}

	public function stop ()
	{
		// Calculate duration
		$duration = microtime (true) - $this->start;

		Logger::getInstance ()->log ('DONE ' . $this->process . ' took ' . number_format ($duration, 3) . 's');
	}

} 