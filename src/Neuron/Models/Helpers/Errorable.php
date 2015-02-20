<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/05/14
 * Time: 11:27
 */

namespace Neuron\Models\Helpers;
use Neuron\Collections\ErrorCollection;

/**
 * Class Errorable
 *
 * Provide some default methods to set and return errors.
 *
 * @package Neuron\Models\Errorable
 */
abstract class Errorable
{

	/**
	 * @var string array
	 */
	private $errors = null;

	private function touchErrors ()
	{
		if (!isset ($this->errors)) {
			$this->setErrors (new ErrorCollection ());
		}
	}

	/**
	 * @param string $error
	 */
	public function setError ($error)
	{
		$this->addError ($error);
	}

	/**
	 * Set the error array. By reference!
	 */
	public function setErrors (ErrorCollection $errors){
		$this->errors = $errors;
	}

	/**
	 * @return string|null
	 */
	public function getError ()
	{
		$this->touchErrors ();
		if (count ($this->errors) > 0)
		{
			return end ($this->errors);
		}
		return null;
	}

	/**
	 * @param $error
	 */
	public function addError ($error)
	{
		$args = func_get_args ();
		array_shift ($args);

		$this->touchErrors ();
		$this->errors[] = sprintf ($error, $args);
	}

	/**
	 * @return ErrorCollection
	 */
	public function getErrors ()
	{
		$this->touchErrors ();
		return $this->errors;
	}

} 