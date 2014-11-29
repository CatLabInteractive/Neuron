<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 12:31
 */

namespace Neuron;

use Neuron\Exceptions\DataNotSet;
use Neuron\Net\Request;
use Neuron\Router;

class Application {

	/** @var Router $router */
	private $router;

	/** @var string $locale */
	private $locale;

	private static $in;

	/**
	 * @return Application
	 */
	public static function getInstance ()
	{
		if (!isset (self::$in))
		{
			self::$in = new self ();
		}
		return self::$in;
	}

	/**
	 *
	 */
	private function __construct ()
	{
		\Neuron\Core\Template::addPath (dirname (dirname (__FILE__)) . '/templates/', '', -1);
	}

	/**
	 * @param Router $router
	 */
	public function setRouter (Router $router)
	{
		$this->router = $router;
	}

	/**
	 * @param string $locale
	 * @throws DataNotSet
	 */
	public function setLocale ($locale)
	{
		$this->locale = $locale;

		// Also let php know
		putenv ("LANG=" . $this->locale);

		$result = setlocale (LC_ALL, $this->locale);
		if (!$result)
		{
			throw new DataNotSet ("Locale " . $locale . " is not available on this platform.");
		}
	}

	/**
	 * @return string
	 */
	public function getLocale ()
	{
		return $this->locale;
	}

	/**
	 * Check if locale is set, and if not, set it to english.
	 */
	private function checkLocale ()
	{
		if (!isset ($this->locale))
		{
			$this->setLocale ('en');
		}
	}

	/**
	 * @throws DataNotSet
	 */
	public function dispatch (\Neuron\Net\Request $request = null)
	{
		$this->checkLocale ();

		if (!isset ($this->router))
		{
			throw new DataNotSet ("Application needs a router.");
		}

		if (!isset ($request))
		{
			$request = Request::fromInput ();
		}

		$this->router->run ($request);
	}
}