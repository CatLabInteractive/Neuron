<?php

namespace Neuron\Tests;

use Neuron\DB\MySQL;

/**
 * A MySQL driver whose connection attempt is scripted, so the failure path
 * can be tested without the mysqli extension or a server.
 */
class FailingMySQL extends MySQL
{
	/** @var \Throwable|null thrown by openConnection () */
	public $openError;

	/** @var object|null returned by openConnection () */
	public $fakeConnection;

	/** @var int */
	public $attempts = 0;

	protected function openConnection ()
	{
		$this->attempts ++;

		if ($this->openError) {
			throw $this->openError;
		}

		return $this->fakeConnection;
	}
}
