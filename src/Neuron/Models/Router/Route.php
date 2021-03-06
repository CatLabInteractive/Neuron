<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/01/15
 * Time: 15:44
 */

namespace Neuron\Models\Router;


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

	/** @var mixed[] */
	private $extraParameters = array ();

	public function __construct ($path)
	{
		// Regex are too pro, bro! Give us some simple {param} and {param?} parameters.
		$path = preg_replace ('/\/\{\w+\\?}/', '(/\w+)?', $path);
		$path = preg_replace ('/\/\{\w+\}/', '(/\w+)', $path);

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
	 * @return $this
	 */
	public function filter ($filtername)
	{
		$arguments = func_get_args ();
		array_shift ($arguments);

		$filters = explode (',', $filtername);
		foreach ($filters as $filter)
		{
			$objfilter = new Filter ($filter);
			$objfilter->setArguments ($arguments);

			$this->filters[] = $objfilter;
		}

		return $this;
	}

	/**
	 * @return \mixed[]
	 */
	public function getParameters ()
	{
		return $this->extraParameters;
	}

	/**
	 * Add additional parameters that will be added to the controller call.
	 * @return $this
	 */
	public function with () {

		$this->extraParameters = func_get_args ();
		return $this;
	}

	/**
	 * @return Filter[]
	 */
	public function getFilters ()
	{
		return $this->filters;
	}

}