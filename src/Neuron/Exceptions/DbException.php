<?php


namespace Neuron\Exceptions;


class DbException
	extends \Exception
{
	/** MySQL error number for a UNIQUE / PRIMARY KEY violation. */
	const DUPLICATE_ENTRY = 1062;

	private $query;

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
