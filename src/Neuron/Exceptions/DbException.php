<?php


namespace Neuron\Exceptions;


class DbException
	extends \Exception
{
	private $query;

	private $mysqlErrorCode;

	/**
	 * @param string $query
	 */
	public function setQuery ($query)
	{
		$this->query = $query;
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
}
