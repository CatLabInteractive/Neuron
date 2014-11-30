<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 30/11/14
 * Time: 18:17
 */

namespace Neuron\Models;
use Neuron\Interfaces\Observer;

/**
 * Class Observable
 *
 * Allows observing in two ways:
 * - Listen for any event using "observe (Observable $a)"
 * or
 * - Listen to specific events using "on (callable $callback)"
 *
 * @package Neuron\Models
 */
abstract class Observable {

	/** @var array */
	private $events = array ();

	/** @var Observer[] */
	private $observing = array ();

	/**
	 * Trigger an event, possibly with parameters.
	 * @param $event
	 */
	protected function trigger ($event)
	{
		$arguments = func_get_args ();
		array_shift ($arguments);

		// Check for events
		if (isset ($this->events[$event]))
		{
			foreach ($this->events[$event] as $call)
			{
				call_user_func_array ($call, $arguments);
			}
		}

		// Notify everyone
		foreach ($this->observing as $observer)
		{
			$observer->update ();
		}
	}

	/**
	 * Listen to specific events
	 * @param $event
	 * @param callable $callback
	 */
	public function on ($event, callable $callback)
	{
		if (!isset ($this->events[$event]))
		{
			$this->events[$event] = array ();
		}
		$this->events[$event][] = $callback;
	}

	/**
	 * @param Observer $observer
	 */
	public function observe (Observer $observer)
	{
		$this->observing[] = $observer;
	}
}