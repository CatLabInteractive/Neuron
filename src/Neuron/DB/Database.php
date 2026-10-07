<?php
namespace Neuron\DB;

// Let's just point the database to MySQL
use Neuron\Interfaces\Logger;

abstract class Database
{
	protected $insert_id = 0;
	protected $affected_rows = 0;
	protected $query_counter = 0;

	/** The query log keeps at most this many statements (the most recent). */
	const QUERY_LOG_LIMIT = 5000;

	/** @var bool See setQueryLogEnabled (). */
	private static $queryLogEnabled = false;

	protected $query_log = array ();

	protected $origin_counter = array ();

	/** @var Logger */
	private $logger;

	/**
	 * @param string $id
	 * @return Database
	 */
	public static function __getInstance ($id = 'general')
	{
		static $in;

		if (!isset ($in))
		{
			$in = array ();
		}

		if (!isset ($in[$id]))
		{
			$tmp = new MySQL ();
			$in[$id] = $tmp;
		}

		return $in[$id];
	}

	public function connect ()
	{

	}

	public function disconnect ()
	{

	}

	public function setLogger (Logger $logger)
	{
		$this->logger = $logger;
	}

	/** @var Database|null Test-only override, set via setInstance() */
	private static $testInstance = null;

	/**
	 * Override the singleton instance for testing purposes.
	 * @param Database|null $instance
	 */
	public static function setInstance (?Database $instance)
	{
		self::$testInstance = $instance;
	}

	/**
	 * @return Database
	 */
	public static function getInstance ()
	{
		if (self::$testInstance !== null) {
			return self::$testInstance;
		}
		return self::__getInstance ();
	}

	public function getInsertId ()
	{
		return $this->insert_id;
	}

	/**
	 * @return int
	 */
	public function getAffectedRows ()
	{
		return $this->affected_rows;
	}

	/**
	 * @return int
	 */
	public function getQueryCounter ()
	{
		return $this->query_counter;
	}

	// Abstract functions
	/**
	 * @param $sSQL
	 * @return Result|int
	 */
	public abstract function query($sSQL);

	public abstract function multiQuery($sSQL);

	public abstract function escape ($txt);

	public function start ()
	{
		$this->query ("START TRANSACTION");
	}

	public function commit ()
	{
		$this->query ("COMMIT");
	}

	public function rollback ()
	{
		$this->query ("ROLLBACK");
	}

	/**
	 * Just put a comment in the query log (when it is enabled).
	 * Does not connect to database.
	 * @param $txt
	 */
	public function log ($txt)
	{
		$this->addQueryLog ("/* " . $txt . " */");
	}

	/**
	 * Keep the executed statements (with their bound values) in memory, so
	 * getAllQueries () and getLastQuery () can return them. Off by default:
	 * turn it on only where the application shows or inspects the log.
	 * Applies to every Database instance.
	 * @param bool $enabled
	 */
	public static function setQueryLogEnabled ($enabled)
	{
		self::$queryLogEnabled = (bool) $enabled;
	}

	/**
	 * @return bool
	 */
	public static function isQueryLogEnabled ()
	{
		return self::$queryLogEnabled;
	}

	protected function addQueryLog ($sSQL, $duration = null)
	{
		$stacktrace = debug_backtrace (DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		$origin = $stacktrace[1];

		if (isset ($stacktrace[2]['class']) && $stacktrace[2]['class'] == 'Neuron\DB\Query')
		{
			$origin = $stacktrace[2];
		}

		// The origin counters hold no statement text, so they are always kept.
		if (isset ($origin['file']))
		{
			$this->increaseOriginCounter ($origin['file'], $origin['line']);
		}

		if (!self::$queryLogEnabled && !isset ($this->logger))
		{
			return;
		}

		$txt = '[' . number_format ((float) $duration, 3) . ' s] ';
		$txt .= trim ($sSQL);

		// A logger is a sink the application set itself; it is not gated.
		if (isset ($this->logger))
		{
			$color = 'green';
			if (strpos ($txt, 'START') !== false || strpos ($txt, 'COMMIT') !== false || strpos ($txt, 'ROLLBACK') !== false)
			{
				$color = 'red';
			}

			$this->logger->log ('DB: ' . preg_replace('!\s+!', ' ', str_replace ("\t", " ", str_replace ("\n", "", $txt))), false, $color);
		}

		if (!self::$queryLogEnabled)
		{
			return;
		}

		$this->query_log[] = $txt;

		if (count ($this->query_log) > self::QUERY_LOG_LIMIT)
		{
			array_shift ($this->query_log);
		}
	}

	/**
	 * @return string|null The most recent logged statement, null when the
	 * query log is off or empty.
	 */
	public function getLastQuery ()
	{
		$count = count ($this->query_log);
		return $count > 0 ? $this->query_log[$count - 1] : null;
	}

	/**
	 * @return string[] The logged statements; empty unless the query log was
	 * turned on with setQueryLogEnabled (true).
	 */
	public function getAllQueries ()
	{
		return $this->query_log;
	}

	public function getConnection ()
	{

	}

	private function increaseOriginCounter ($file, $line)
	{
		if (!isset ($this->origin_counter[$file . ':' . $line]))
		{
			$this->origin_counter[$file . ':' . $line] = 1;
		}
		else
		{
			$this->origin_counter[$file . ':' . $line] ++;
		}
	}

	public function getOriginCounters ()
	{
		arsort ($this->origin_counter, SORT_NUMERIC);

		$out = array ();
		foreach ($this->origin_counter as $k => $v)
		{
			$out[] = '<strong>Queries: ' . $v . '</strong>: ' . $k;
		}
		return $out;
	}

	// Functions that should not be used... but well, we can't do without them at the moment
	public abstract function fromUnixtime ($timestamp);
	public abstract function toUnixtime ($date);
	
	public function flushLog ()
	{
		$this->query_log = array ();
		$this->origin_counter = array ();
		$this->query_counter = 0;
	}
}
?>
