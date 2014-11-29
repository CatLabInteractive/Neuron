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
	 * @throws DataNotSet
	 */
	public function dispatch (\Neuron\Net\Request $request = null)
	{
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