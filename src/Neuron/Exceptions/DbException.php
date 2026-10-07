<?php


namespace Neuron\Exceptions;


class DbException
	extends \Exception
{
	/** MySQL error number for a UNIQUE / PRIMARY KEY violation. */
	const DUPLICATE_ENTRY = 1062;

	/** The message of every connection failure; it never carries driver detail. */
	const CONNECTION_FAILED = 'Database connection failed.';

	private $query;

	private $connectionError = false;

	private $mysqlErrorCode;

	/**
	 * Wrap a mysqli_sql_exception (thrown by mysqli itself since PHP 8.1)
	 * so callers only ever have to catch DbException.
	 * @param \mysqli_sql_exception $e
	 * @param string|null $query
	 * @return DbException
	 */
	public static function fromMysqliException (\mysqli_sql_exception $e, $query = null)
	{
		$ex = new self ('MySQL Error: ' . $e->getMessage (), $e->getCode (), $e);
		$ex->setErrorCode ($e->getCode ());
		$ex->setQuery ($query);
		return $ex;
	}

	/**
	 * The connection (or its charset) could not be set up. The message is
	 * fixed; what the driver reported is kept as the previous exception, for
	 * logging only: it can name the host and the user.
	 * @param string $driverMessage
	 * @param int|string $driverCode
	 * @return DbException
	 */
	public static function connectionFailed ($driverMessage, $driverCode = 0)
	{
		$code = is_numeric ($driverCode) ? intval ($driverCode) : 0;

		// A new exception rather than the driver's own: its stack trace does
		// not hold the arguments of the connect call.
		$cause = new \RuntimeException ((string) $driverMessage, $code);

		$ex = new self (self::CONNECTION_FAILED, $code, $cause);
		$ex->setErrorCode ($code);
		$ex->connectionError = true;
		return $ex;
	}

	/**
	 * True when the connection could not be set up, as opposed to a
	 * statement that failed.
	 * @return bool
	 */
	public function isConnectionError ()
	{
		return $this->connectionError;
	}

	/**
	 * @param string $query
	 */
	public function setQuery ($query)
	{
		$this->query = $query;
	}

	/**
	 * @return string|null
	 */
	public function getQuery ()
	{
		return $this->query;
	}

	/**
	 * @param $status
	 * @return $this
	 */
	public function setErrorCode($status)
	{
		$this->mysqlErrorCode = $status;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getErrorCode()
	{
		return $this->mysqlErrorCode;
	}

	/**
	 * True when the query violated a UNIQUE or PRIMARY KEY, e.g. because a
	 * concurrent request inserted the same row first.
	 * @return bool
	 */
	public function isDuplicateEntry ()
	{
		return intval ($this->mysqlErrorCode) === self::DUPLICATE_ENTRY;
	}
}
