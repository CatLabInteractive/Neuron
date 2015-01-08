<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/01/15
 * Time: 15:44
 */

namespace Neuron\Models;


use Neuron\Interfaces\Module;

class Route {

	/** @var string $route */
	private $route;

	/** @var callable $function */
	private $function;

	/** @var Module $module */
	private $module;

	/** @var string[] */
	private $filters = array ();

	public function __construct ($path)
	{
		$this->setRoute ($path);
	}

	/**
	 * @return string
	 */
	public function getRoute ()
	{
		return $this->route;
	}

	/**
	 * @param string $route
	 */
	public function setRoute ($route)
	{
		$this->route = $route;
	}

	/**
	 * @return callable
	 */
	public function getFunction ()
	{
		return $this->function;
	}

	/**
	 * @param callable $function
	 */
	public function setFunction ($function)
	{
		$this->function = $function;
	}

	/**
	 * @return Module
	 */
	public function getModule ()
	{
		return $this->module;
	}

	/**
	 * @param Module $module
	 */
	public function setModule ($module)
	{
		$this->module = $module;
	}

	/**
	 * Add a filter
	 * @param $filtername
	 */
	public function filter ($filtername)
	{
		$this->filters[] = $filtername;
	}

	/**
	 * @return string[]
	 */
	public function getFilters ()
	{
		return $this->filters;
	}

}