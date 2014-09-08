<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 28/04/14
 * Time: 16:26
 */

namespace Neuron\Filter;


use Exception;

class Context
{
	private $fields = array ();

	/**
	 * @param Field $field
	 */
	public function setField (Field $field)
	{
		$this->fields[$field->getName ()] = $field;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function hasField ($name)
	{
		return isset ($this->fields[$name]);
	}

	/**
	 * @param $name
	 * @return Field
	 */
	public function getField ($name)
	{
		if (isset ($this->fields[$name]))
		{
			return $this->fields[$name];
		}
		return null;
	}
} 