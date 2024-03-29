<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/04/14
 * Time: 15:38
 */

namespace Neuron\Collections;

use ArrayAccess;
use Countable;
use Iterator;
use Neuron\Models\Observable;

class Collection
	extends Observable
	implements Iterator, ArrayAccess, Countable
{
	private $position = 0;
	private $data = array ();

	protected function setCollectionValues (array $data)
	{
		$this->data = $data;
	}

	public function add ($value) {
		$this[] = $value;
	}

	 /**
	  * (PHP 5 &gt;= 5.0.0)<br/>
	  * Whether a offset exists
	  * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	  * @param mixed $offset <p>
	  * An offset to check for.
	  * </p>
	  * @return boolean true on success or false on failure.
	  * </p>
	  * <p>
	  * The return value will be casted to boolean if non-boolean was returned.
	  */
	 public function offsetExists($offset)
	 {
		 return isset ($this->data[$offset]);
	 }

	 /**
	  * (PHP 5 &gt;= 5.0.0)<br/>
	  * Offset to retrieve
	  * @link http://php.net/manual/en/arrayaccess.offsetget.php
	  * @param mixed $offset <p>
	  * The offset to retrieve.
	  * </p>
	  * @return mixed Can return all value types.
	  */
	 public function offsetGet($offset)
	 {
		 return $this->data[$offset];
	 }

	 /**
	  * (PHP 5 &gt;= 5.0.0)<br/>
	  * Offset to set
	  * @link http://php.net/manual/en/arrayaccess.offsetset.php
	  * @param mixed $offset <p>
	  * The offset to assign the value to.
	  * </p>
	  * @param mixed $value <p>
	  * The value to set.
	  * </p>
	  * @return void
	  */
	 public function offsetSet($offset, $value)
	 {
		 if (is_null ($offset))
		 {
			 $index = array_push ($this->data, $value) - 1;
			 $this->trigger ('add', $value, $index);
		 }
		 else
		 {
			$this->data[$offset] = $value;

			 if (isset ($this->data[$offset]))
			 {
				 $this->trigger ('add', $value, $offset);
			 }
			 else {
				 $this->trigger ('set', $value, $offset);
			 }
		 }
	 }

	 /**
	  * (PHP 5 &gt;= 5.0.0)<br/>
	  * Offset to unset
	  * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	  * @param mixed $offset <p>
	  * The offset to unset.
	  * </p>
	  * @return void
	  */
	 public function offsetUnset($offset)
	 {
		 $value = isset ($this->data[$offset]) ? $this->data[$offset] : null;
		 unset ($this->data[$offset]);
		 $this->trigger ('unset', $value, $offset);
	 }

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current()
	{
		if (isset ($this->data[$this->position]))
		{
			return $this->data[$this->position];
		}
		return null;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return mixed Any returned value is ignored.
	 */
	public function next()
	{
		++$this->position;
		return $this->current ();
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid()
	{
		return isset($this->data[$this->position]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 */
	public function count()
	{
		return count ($this->data);
	}

	/**
	 * Return the next element without increasing position.
	 * @return mixed|null
	 */
	public function peek()
	{
		if (isset ($this->data[$this->position + 1]))
		{
			return $this->data[$this->position + 1];
		}
		return null;
	}

	public function reverse ()
	{
		$this->data = array_reverse ($this->data);
	}

	private function isAssociative () {
		return array_values ($this->data) === $this->data;
	}

	/**
	 * Remove an element from the collection.
	 * @param $entry
	 * @return bool
	 */
	public function remove ($entry) {
		foreach ($this->data as $k => $v) {
			if ($v === $entry) {
				$associative = $this->isAssociative ();

				unset ($this->data[$k]);
				if ($associative) {
					$this->data = array_values ($this->data);
				}

				return true;

			}
		}
		return false;
	}

	/**
	 * Return the very first element.
	 */
	public function first ()
	{
		if (!is_array($this->data)) return $this->data;
		if (!count($this->data)) return null;
		reset($this->data);
		return $this->data[key($this->data)];
	}

	/**
	 * Return the very last element.
	 */
	public function last ()
	{
		if (!is_array($this->data)) return $this->data;
		if (!count($this->data)) return null;
		end($this->data);
		return $this->data[key($this->data)];
	}

	/**
	 * Clear array
	 */
	public function clear ()
	{
		$this->data = array ();
		$this->rewind ();
	}
}
