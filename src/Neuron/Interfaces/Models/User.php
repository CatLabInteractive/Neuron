<?php
namespace Neuron\Interfaces\Models;

interface User
	extends \Neuron\Interfaces\Model {

	/**
	 * @param bool $formal
	 * @return string
	 */
	public function getDisplayName ($formal = false);

	/**
	 * @return string
	 */
	public function getEmail ();

}
