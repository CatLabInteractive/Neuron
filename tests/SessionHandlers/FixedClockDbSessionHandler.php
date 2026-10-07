<?php

namespace Neuron\Tests\SessionHandlers;

use Neuron\SessionHandlers\DbSessionHandler;

/**
 * DbSessionHandler with a fixed clock and lifetime, so the expiry
 * decisions can be tested without touching the session ini settings.
 */
class FixedClockDbSessionHandler extends DbSessionHandler
{
	/** @var int */
	public $now = 100000;

	/** @var int */
	public $maxLifetime = 1000;

	protected function now ()
	{
		return $this->now;
	}

	protected function getMaxLifetime ()
	{
		return $this->maxLifetime;
	}
}
