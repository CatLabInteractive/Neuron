<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 26/01/15
 * Time: 11:52
 */

namespace Neuron;


use Neuron\Exceptions\DataNotSet;
use Neuron\Exceptions\InvalidParameter;

class MapperFactory
{

	public static function getInstance ()
	{
		static $in;
		if (!isset ($in)) {
			$in = new self ();
		}
		return $in;
	}

	private $mapped = array ();

	public function setMapper ($key, $mapper)
	{
		if (isset ($this->mapped[$key]))
			throw new InvalidParameter ("Mapper with name " . $key . " is already set.");

		$this->mapped[$key] = $mapper;
	}

	public function getMapper ($key)
	{
		if (isset ($this->mapped[$key])) {
			return $this->mapped[$key];
		} else {
			throw new DataNotSet ("Mapper " . $key . " was not registered.");
		}
	}

	/**
	 * @return \Neuron\Interfaces\Mappers\UserMapper
	 */
	public static function getUserMapper ()
	{
		return self::getInstance ()->getMapper ('user');
	}

}