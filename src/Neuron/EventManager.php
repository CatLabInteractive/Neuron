<?php
/**
* Used mostly for cross module communication.
* Try to always prefix with module name
* Can also be extended to give objects events
*/

namespace Neuron;
use Neuron\Exceptions\InvalidParameter;


/**
 * Class EventManager
 *
 * The event manager allows us to easily write extensions to Cloudwalkers.
 *
 * @package bmgroup\Cloudwalkers
 */
class EventManager {

	private $triggers = array ();

	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}
		return $in;
	}

	private function __construct ()
	{

	}

	public static function trigger ($event)
	{
		$in = self::getInstance ();
		if (isset ($in->events))
		{
			$args = func_get_args ();
			foreach ($in->events as $method)
			{
				call_user_func_array ($method, $args);
			}
		}
	}

	public static function on ($event, $callback)
	{
		if (!is_callable ($callback))
		{
			throw new InvalidParameter ("All callbacks of events must be callables.");
		}

		$in = self::getInstance ();
		if (!isset ($in->triggers[$event]))
		{
			$in->triggers[$event] = array ();
		}
		$in->triggers[$event] = $callback;
	}

	public static function off ($event)
	{
		$in = self::getInstance ();
		$in->triggers[$event] = array ();
	}

} 