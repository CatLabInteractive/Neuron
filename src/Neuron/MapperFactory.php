<?php


namespace Neuron;



class MapperFactory
{
	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}
		return $in;
	}

	private $mapped = array ();

	public function setMapper ($key, $mapper)
	{
		$this->mapped[$key] = $mapper;
	}

	public function getMapper ($key, $default)
	{
		if (isset ($this->mapped[$key]))
		{
			return $this->mapped[$key];
		}
		else
		{
			$this->mapped[$key] = new $default ();
		}
		return $this->mapped[$key];
	}

	/**
	 * @return \Neuron\Mappers\UserMapper
	 */
	public static function getUserMapper ()
	{
		return self::getInstance ()->getMapper ('user', 'Neuron\Mappers\UserMapper');
	}

	/**
	 * @return \Neuron\Mappers\EmailMapper
	 */
	public static function getEmailMapper ()
	{
		return self::getInstance ()->getMapper ('email', 'Neuron\Mappers\EmailMapper');
	}

	/**
	 * @return \Neuron\Mappers\AccountsMapper
	 */
	public static function getAccountMapper ()
	{
		return self::getInstance ()->getMapper ('account', 'Neuron\Mappers\AccountsMapper');	
	}
}