<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 12:31
 */

namespace Neuron;

use Neuron\Exceptions\DataNotSet;
use Neuron\Router;

class Application {

	/** @var Router $router */
	private $router;

	public function __construct ()
	{

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
	public function run ()
	{
		if (!isset ($this->router))
		{
			throw new DataNotSet ("Application needs a router.");
		}

		$this->router->run ();
	}
}