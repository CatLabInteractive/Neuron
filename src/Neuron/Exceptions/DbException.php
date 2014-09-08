<?php


namespace Neuron\Exceptions;

use Neuron\Core\Error;


class DbException
	extends Error
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
