<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 28/04/14
 * Time: 15:05
 */

namespace Neuron\Filter;


class TemporaryField extends Field
{
	private $value;

	public function __construct ()
	{

	}

	public function setValue ($value)
	{
		$this->value = $value;
	}

	public function getValue ($model)
	{
		return $this->value;
	}
}
