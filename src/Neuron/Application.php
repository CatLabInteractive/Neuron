<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 12:31
 */

namespace Neuron;

use Neuron\Core\Template;
use Neuron\Exceptions\DataNotSet;
use Neuron\Models\Observable;
use Neuron\Net\Request;
use Neuron\Net\Session;
use Neuron\SessionHandlers\DefaultSessionHandler;
use Neuron\SessionHandlers\SessionHandler;

class Application
	extends Observable
{

	/** @var Router $router */
	private $router;

	/** @var string $locale */
	private $locale;

	/** @var SessionHandler $sessionHandler */
	private $sessionHandler;

	private static $in;

	/**
	 * @return Application
	 */
	public static function getInstance ()
	{
		if (!isset (self::$in))
		{
			self::$in = new self ();
		}
		return self::$in;
	}

	/**
	 *
	 */
	private function __construct ()
	{
		Template::addPath (dirname (dirname (__FILE__)) . '/templates/', '', -1);
	}

	/**
	 * @param Router $router
	 */
	public function setRouter (Router $router)
	{
		$this->router = $router;
		$this->trigger ('router:set');
	}

	/**
	 * @return Router
	 */
	public function getRouter ()
	{
		return $this->router;
	}

	/**
	 * @param string $locale
	 * @throws DataNotSet
	 */
	public function setLocale ($locale)
	{
		$this->locale = $locale;

		// Also let php know
		putenv ("LANG=" . $this->locale);

		$result = setlocale (LC_ALL, $this->locale);
		if (!$result)
		{
			throw new DataNotSet ("Locale " . $locale . " is not available on this platform.");
		}
		$this->trigger ('locale:set');
	}

	/**
	 * @return string
	 */
	public function getLocale ()
	{
		return $this->locale;
	}

	/**
	 * Check if locale is set, and if not, set it to english.
	 */
	private function checkLocale ()
	{
		if (!isset ($this->locale))
		{
			$this->setLocale ('en_GB.utf8');
		}
	}

	/**
	 * @param SessionHandler $handler
	 */
	public function setSessionHandler (SessionHandler $handler)
	{
		$this->sessionHandler = $handler;
	}

	/**
	 * @return SessionHandler
	 */
	private function getSessionHandler ()
	{
		if (!isset ($this->sessionHandler))
		{
			$this->sessionHandler = new DefaultSessionHandler ();
		}

		return $this->sessionHandler;
	}

	/**
	 * @param Request $request
	 * @throws DataNotSet
	 */
	public function dispatch (Request $request = null)
	{
		// Trigger initialize
		$this->trigger ('dispatch:initialize');

		// Check locales
		$this->checkLocale ();

		if (!isset ($this->router))
		{
			throw new DataNotSet ("Application needs a router.");
		}

		if (!isset ($request))
		{
			$request = Request::fromInput ();
		}

		// Set session from the session handler
		$session = new Session ($this->getSessionHandler ());
		$session->connect ();

		// Set session in request
		$request->setSession ($session);

		// Trigger before
		$this->trigger ('dispatch:before', $request);

		// Run router
		$this->router->run ($request);

		// Trigger dispatch
		$this->trigger ('dispatch:after', $request);

		// Disconnect the session
		$session->disconnect ();

		// End
		$this->trigger ('dispatch:terminate');
	}
}