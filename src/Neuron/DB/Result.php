<?php

namespace Neuron\DB;

use Iterator;
use ArrayAccess;
use Countable;

/**
 * Class Result
 * @package Neuron\DB
 */
class Result implements Iterator, ArrayAccess, Countable
{
	private $result;
	
	// iterator
	private $rowId = 0;
	private $current;
	
	private $cache;

	public function __construct ($result)
	{
		$this->result = $result;
		
		// Move to the first row
		/*
		$this->rowId = 0;
		$this->current = $this->result->fetch_assoc ();
		*/
	}
	
	public function getNumRows ()
	{
		return $this->result->num_rows;
	}
	
	/**************************
		ARRAY ACCESS
	***************************/
	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return $offset >= 0 && $offset < $this->getNumRows ();
	}
	
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		// Only numeric values
		$offset = intval ($offset);
	
		// Cache these calls
		if (!isset ($this->cache[$offset]))
		{
			// Move mysql data stream to offset
			$this->result->data_seek ($offset);
			$this->cache[$offset] = $this->result->fetch_assoc ();
		
			// Move back to current position
			$this->result->data_seek ($this->rowId);
		}
		
		return $this->cache[$offset];
	}
	
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		// Doesn't do anything here.
	}
	
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		// Doesn't do anything here.
	}

	
	/**************************
		ITERATOR
	***************************/
	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this->current;
	}
	
	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->rowId;
	}
	
	#[\ReturnTypeWillChange]
	public function next()
	{
		$this->rowId ++;
		$this->current = $this->result->fetch_assoc ();
		
		return $this->current;
	}
	
	#[\ReturnTypeWillChange]
	public function rewind()
	{
		$this->rowId = 0;
		$this->result->data_seek (0);
		$this->current = $this->result->fetch_assoc ();
	}
	
	#[\ReturnTypeWillChange]
	public function valid()
	{
		return is_array ($this->current ());
	}
	
	/**************************
		COUNTABLE
	***************************/
	#[\ReturnTypeWillChange]
	public function count ()
	{
		return $this->getNumRows ();
	}
	
	// Destruct
	public function __destruct ()
	{
		$this->result->close ();
	}
}
?>
