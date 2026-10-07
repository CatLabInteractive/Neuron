<?php

namespace Neuron\Tests;

use Neuron\Net\Session;
use Neuron\SessionHandlers\SessionHandler;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
	public function testRegenerateDoesNothingWithoutAnActiveSession ()
	{
		$this->assertSame (PHP_SESSION_NONE, session_status ());

		$session = new Session (new SessionHandler ());
		$this->assertFalse ($session->regenerate ());

		$this->assertSame (PHP_SESSION_NONE, session_status ());
	}
}
