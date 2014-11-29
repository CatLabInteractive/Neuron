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
	private static $in;

	/** @var string $folder */
	private $folder = '.';

	/** @var string $environment */
	private $environment;

	/** @var mixed[] $files */
	private $files;

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
	 * Load a file in case it's not loaded yet.
	 * @param $file
	 */
	private function loadFile ($file)
	{
		if (!isset ($this->files[$file]))
		{
			$filename = $this->folder . $file . '.php';

			// First load these
			if (file_exists ($filename))
			{
				$this->files[$file] = include ($filename);
			}

			// Now overload with environment values
			if (isset ($this->environment))
			{
				$filename = $this->folder . $this->environment . '/' . $file . '.php';
				if (file_exists ($filename))
				{
					$this->merge ($file, include ($filename));
				}
			}

		}
	}

	/**
	 * @param string $file
	 * @param mixed[] $newData
	 */
	private function merge ($file, $newData)
	{
		$this->files[$file] = array_replace_recursive ($this->files[$file], $newData);
	}

	/**
	 * Find a config variable and return it.
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	private function getValue ($name, $default)
	{
		$parts = explode ('.', $name);
		$file = array_shift ($parts);

		$this->loadFile ($file);

		if (! isset ($this->files[$file])) {
			return $default;
		}
		else {
			$out = $this->files[$file];
			foreach ($parts as $part)
			{
				if (!isset ($out[$part]))
				{
					return $default;
				}
				else {
					$out = $out[$part];
				}
			}
		}

		return $out;
	}
}