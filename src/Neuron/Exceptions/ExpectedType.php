<?php


namespace Neuron\Exceptions;


class ExpectedType
	extends InvalidParameter
{
	/**
	 * @param $obj
	 * @param $className
	 * @throws ExpectedType
	 */
	public static function check ($obj, $className)
	{
		if (! ($obj instanceof $className))
			throw new self ($className);
	}

	public function __construct ($class)
	{
		parent::__construct ("Expected object of class " . $class);
	}

}