<?php


namespace Neuron\Interfaces;

use Neuron\Page;


interface FrontController
{
	/**
	 * @return bool
	 */
	public function canDispatch ();

	/**
	 * @param Page $page
	 * @return \Neuron\Net\Response
	 */
	public function dispatch (Page $page);

	/**
	 * @param FrontController $input
	 * @return mixed
	 */
	public function setParentController (FrontController $input);

	/**
	 * @return string
	 */
	public function getName (); // Used for tracking

	/**
	 * @param null $id
	 * @return string
	 */
	public function getInput ($id = null);
};