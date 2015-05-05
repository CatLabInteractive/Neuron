<?php
namespace Neuron\Interfaces\Models;

interface User
	extends \Neuron\Interfaces\Model {

	/**
	 * @return string
	 */
	public function getUsername ();

	/**
	 * @return string
	 */
	public function getEmail ();

}