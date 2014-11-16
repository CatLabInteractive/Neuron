<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 22/08/14
 * Time: 12:59
 */

namespace Neuron;


class Environment {

	/**
	 * Clears all singletons in the Neuron framework, 
	 * to simulate a complete environment reset.
	 */
	public static function destroy ()
	{
		\Neuron\Session::getInstance ()->disconnect ();
		\Neuron\Session::clearInstance ();
		\Neuron\FrontController::destroy ();
		\Neuron\Core\Template::clearShares ();
	}
	
} 