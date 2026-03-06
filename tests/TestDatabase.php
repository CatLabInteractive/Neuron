<?php

namespace Neuron\Tests;

use Neuron\DB\Database;
use Neuron\DB\Result;

/**
 * A Database stub that performs MySQL-compatible escaping in pure PHP.
 * Allows Query tests to run without an actual MySQL connection.
 */
class TestDatabase extends Database
{
	/**
	 * Escape a string using the same rules as mysqli::real_escape_string.
	 */
	public function escape ($txt): string
	{
		return strtr ((string) $txt, [
			"\\"   => "\\\\",
			"\x00" => "\\0",
			"\n"   => "\\n",
			"\r"   => "\\r",
			"'"    => "\\'",
			'"'    => '\\"',
			"\x1a" => "\\Z",
		]);
	}

	public function query ($sSQL): int
	{
		return 0;
	}

	public function multiQuery ($sSQL): int
	{
		return 0;
	}

	public function fromUnixtime ($timestamp): string
	{
		return date ('Y-m-d H:i:s', $timestamp);
	}

	public function toUnixtime ($date): int
	{
		return strtotime ($date);
	}
}
