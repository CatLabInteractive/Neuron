<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 11:21
 */

namespace Neuron;


class Config {

	/** @var Config $in */
	static $in;

	/** @var string $folder */
	private $folder = '.';

	/** @var string $environment */
	private $environment;

	/**
	 * Get a config variable
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public static function get ($name, $default = null)
	{
		return self::getInstance ()->getValue ($name, $default);
	}

	/**
	 * @param string $folder
	 */
	public static function folder ($folder)
	{
		self::getInstance ()->setFolder ($folder);
	}

	/**
	 * Set the environment (sub folder)
	 * @param $environment
	 */
	public static function environment ($environment)
	{
		self::getInstance ()->setEnvironment ($environment);
	}

	/**
	 * Get an instance.
	 * @return Config
	 */
	private static function getInstance ()
	{
		if (!isset (self::$in))
		{
			self::$in = new self ();
		}
		return self::$in;
	}

	/**
	 * @param string $folder
	 */
	private function setFolder ($folder)
	{
		$this->folder = $folder;
	}

	/**
	 * @param string $environment
	 */
	private function setEnvironment ($environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Find a config variable and return it.
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	private function getValue ($name, $default)
	{
		return $default;
	}
}