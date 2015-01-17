<?php


namespace Neuron\Exceptions;


class ExpectedType
	extends InvalidParameter
{

	public function __construct ($class)
	{
		parent::__construct ("Expected object of class " . $class);
	}

}