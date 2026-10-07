<?php

namespace Neuron\Tests\SessionHandlers;

use Neuron\DB\Database;

/**
 * Stands in for the database behind DbSessionHandler: records every
 * query and answers each SELECT with the rows the test prepared.
 * Escaping follows TestDatabase (MySQL rules in pure PHP).
 */
class SessionsDatabase extends Database
{
	/** @var string[] */
	public $queries = array ();

	/** @var array[] Rows returned for every SELECT. */
	public $rows = array ();

	public function query ($sSQL)
	{
		$sSQL = preg_replace ('/\s+/', ' ', trim ($sSQL));
		$this->queries[] = $sSQL;

		if (strpos ($sSQL, 'SELECT') === 0) {
			return $this->rows;
		}

		return 0;
	}

	public function multiQuery ($sSQL)
	{
		return 0;
	}

	public function escape ($txt)
	{
		return strtr ((string) $txt, array (
			"\\"   => "\\\\",
			"\x00" => "\\0",
			"\n"   => "\\n",
			"\r"   => "\\r",
			"'"    => "\\'",
			'"'    => '\\"',
			"\x1a" => "\\Z",
		));
	}

	public function fromUnixtime ($timestamp)
	{
		return date ('Y-m-d H:i:s', $timestamp);
	}

	public function toUnixtime ($date)
	{
		return strtotime ($date);
	}
}
