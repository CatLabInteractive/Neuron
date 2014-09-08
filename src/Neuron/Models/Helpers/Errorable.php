<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/05/14
 * Time: 11:27
 */

namespace Neuron\Models\Helpers;

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
	private $errors = array ();

	/**
	 * @param string $error
	 */
	public function setError ($error)
	{
		$this->addError ($error);
	}

	/**
	 * @return string|null
	 */
	public function getError ()
	{
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
		$this->errors[] = $error;
	}

	/**
	 * @return string[]
	 */
	public function getErrors ()
	{
		return $this->errors;
	}

} 