<?php


namespace Neuron\Exceptions;


class DbException
	extends \Exception
{
	private $query;

	/**
	 * @param string $query
	 */
	public function setQuery ($query)
	{
		$this->query = $query;
	}
}
?>
