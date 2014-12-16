<?php
namespace Neuron\Interfaces\Models;

interface User {

	/**
	 * @return int
	 */
	public function getId ();

	/**
	 * @return string
	 */
	public function getUsername ();

	/**
	 * @return string
	 */
	public function getEmail ();

}