<?php
namespace Neuron\DB;

use Neuron\Config;
use Neuron\Models\Logger;
use Exception;
use mysqli;
use MySQLi_Result;
use Neuron\Core\Error;
use Neuron\Exceptions\DbException;
use Neuron\URLBuilder;

class MySQL extends Database
{
	/** @var  MySQLi */
	private $connection;
	
	
	/**
	 * @throws DbException with a fixed message when the connection or its
	 * charset cannot be set up. The driver's own error is the exception's
	 * previous; nothing is printed.
	 */
	public function connect ()
	{
		if (isset ($this->connection))
		{
			return;
		}

		Logger::getInstance ()->log ('Connecting database.');

		try
		{
			$connection = $this->openConnection ();
			$this->applyCharset ($connection, Config::get ('database.mysql.charset'));
		}
		catch (\Throwable $e)
		{
			throw DbException::connectionFailed ($e->getMessage (), $e->getCode ());
		}

		$this->connection = $connection;
	}

	/**
	 * Open the connection described by the database.mysql config.
	 * @return mysqli
	 * @throws \Throwable when the connection cannot be made.
	 */
	protected function openConnection ()
	{
		// Have mysqli throw, whatever the report mode: without it a failed
		// connect raises a PHP warning that carries the host and the user.
		$driver = new \mysqli_driver ();
		$reportMode = $driver->report_mode;
		mysqli_report (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		try
		{
			$connection = new MySQLi
			(
				Config::get ('database.mysql.host'),
				Config::get ('database.mysql.username'),
				Config::get ('database.mysql.password'),
				Config::get ('database.mysql.database')
			);
		}
		finally
		{
			mysqli_report ($reportMode);
		}

		if ($connection->connect_errno)
		{
			throw new \RuntimeException ((string) $connection->connect_error, intval ($connection->connect_errno));
		}

		return $connection;
	}

	/**
	 * Set the connection charset through the driver, so escaping uses it too.
	 * @param mysqli $connection
	 * @param string|null $charset nothing is set when empty
	 * @throws \Throwable when the server rejects the charset.
	 */
	protected function applyCharset ($connection, $charset)
	{
		if ($charset === null || $charset === '')
		{
			return;
		}

		if (!$connection->set_charset ($charset))
		{
			throw new \RuntimeException ((string) $connection->error, intval ($connection->errno));
		}
	}

	public function disconnect ()
	{
		Logger::getInstance ()->log ('Disconnecting database.');
		if (isset ($this->connection))
		{
			$this->connection->close ();
		}
		$this->connection = null;
	}
	
	public function getConnection ()
	{
		return $this->connection;
	}

	public function multiQuery ($sSQL)
	{
		$start = microtime (true);

		$this->connect ();

		// Increase the counter
		$this->query_counter ++;

		try
		{
			$result = $this->connection->multi_query (trim ($sSQL));

			// FLUSH RESULTS
			// @TODO make these usable
			do  {
				$r = $this->connection->store_result ();
				if ($r)
				{
					$r->free ();
				}

				if (!$this->connection->more_results ())
				{
					break;
				}

				//$this->connection->next_result();
			} while ($this->connection->next_result ());

			// A failing later statement only shows up as next_result() === false.
			if ($result && $this->connection->errno)
			{
				$result = false;
			}
		}
		catch (\mysqli_sql_exception $e)
		{
			throw DbException::fromMysqliException ($e, $sSQL);
		}

		$duration = microtime (true) - $start;
		$this->addQueryLog ($sSQL, $duration);

		if (!$result)
		{
			//var_dump (debug_backtrace ());
			//$data = debug_backtrace ();
			//print_r ($data);


			//echo $sSQL;
			$ex = new DbException ('MySQL Error: '.$this->connection->error);
			$ex->setErrorCode ($this->connection->errno);
			$ex->setQuery ($sSQL);

			throw $ex;
		}

		elseif ($result instanceof MySQLi_Result)
		{
			return new Result ($result);
		}

		// Insert ID will return zero if this query was not insert or update.
		$this->insert_id = intval ($this->connection->insert_id);

		// Affected rows
		$this->affected_rows = intval ($this->connection->affected_rows);

		if ($this->insert_id > 0)
			return $this->insert_id;

		if ($this->affected_rows > 0)
			return $this->affected_rows;

		return $result;
	}
	
	/*
		Execute a query and return a result
	*/
	public function query ($sSQL, $log = true)
	{
		$start = microtime (true);
		
		$this->connect ();
		
		// Increase the counter
		$this->query_counter ++;
		
		try {
			$result = $this->connection->query (trim ($sSQL));
		} catch (\mysqli_sql_exception $e) {
			// PHP 8.1+ mysqli throws itself (MYSQLI_REPORT_STRICT is the default).
			throw DbException::fromMysqliException ($e, $sSQL);
		}
		
		$duration = microtime (true) - $start;

		if ($log) {
			$this->addQueryLog($sSQL, $duration);
		}
		
		if (!$result) {
			$ex = (new DbException ('MySQL Error: '.$this->connection->error))
				->setErrorCode($this->connection->errno);
			$ex->setQuery ($sSQL);
			throw $ex;
		} elseif ($result instanceof MySQLi_Result) {
			return new Result ($result);
		}
		
		// Insert ID will return zero if this query was not insert or update.
		$this->insert_id = intval ($this->connection->insert_id);
		
		// Affected rows
		$this->affected_rows = intval ($this->connection->affected_rows);
		
		if ($this->insert_id > 0)
			return $this->insert_id;
		
		if ($this->affected_rows > 0)
			return $this->affected_rows;
		
		return $result;
	}
	
	public function escape ($txt)
	{
		if (is_array ($txt))
		{
			throw new Error ('Invalid parameter: escape cannot handle arrays.');
		}
		$this->connect ();
		return $this->connection->real_escape_string ($txt);
	}
	
	public function fromUnixtime ($timestamp)
	{
		$query = $this->query ("SELECT FROM_UNIXTIME('{$timestamp}') AS datum", false);
		return $query[0]['datum'];
	}
	
	public function toUnixtime ($date)
	{
		$query = $this->query ("SELECT UNIX_TIMESTAMP('{$date}') AS datum", false);
		return $query[0]['datum'];
	}
}
?>
