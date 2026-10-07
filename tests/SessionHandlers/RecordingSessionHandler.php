<?php

namespace Neuron\Tests\SessionHandlers;

use Neuron\SessionHandlers\SessionHandler;

/**
 * Records write() calls instead of passing them to PHP's own handler.
 */
class RecordingSessionHandler extends SessionHandler
{
	/** @var array[] */
	public $writes = array ();

	#[\ReturnTypeWillChange]
	public function write ($session_id, $session_data)
	{
		$this->writes[] = array ($session_id, $session_data);
		return true;
	}
}
