<?php

namespace Neuron\Tests;

/**
 * A TestDatabase that counts and logs its statements the way the MySQL
 * driver does, without a connection.
 */
class LoggingTestDatabase extends TestDatabase
{
	public function query ($sSQL): int
	{
		$this->query_counter ++;
		$this->addQueryLog ($sSQL, 0.001);
		return 0;
	}
}
