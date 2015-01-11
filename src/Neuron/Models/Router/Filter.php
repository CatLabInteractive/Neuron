<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 11/01/15
 * Time: 13:58
 */

namespace Neuron\Models\Router;


use Neuron\Net\Request;

class Filter {

	/** @var string $name */
	private $name;

	/** @var mixed[] $arguments */
	private $arguments;

	/** @var Request $request */
	private $request;

	public function __construct ($name)
	{
		$this->setName ($name);
	}

	/**
	 * @return string
	 */
	public function getName ()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName ($name)
	{
		$this->name = $name;
	}

	/**
	 * @return \mixed[]
	 */
	public function getArguments ()
	{
		return $this->arguments;
	}

	/**
	 * @param \mixed[] $arguments
	 */
	public function setArguments ($arguments)
	{
		$this->arguments = $arguments;
	}

	/**
	 * @param Request $request
	 */
	public function setRequest (Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Clear the request
	 */
	public function clearRequest ()
	{
		$this->request = null;
	}

	/**
	 * @return Request
	 */
	public function getRequest ()
	{
		return $this->request;
	}
}