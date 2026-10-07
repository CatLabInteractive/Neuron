<?php


namespace Neuron\Net;

use Neuron\SessionHandlers\SessionHandler;

class Session
{
	/** @var SessionHandler */
	private $handler;

	public function __construct (SessionHandler $handler)
	{
		$this->handler = $handler;
	}

	public function getSessionId ()
	{
		return session_id ();
	}

	/**
	 * Start a session
	 * @param string $sessionId
	 */
	public function connect ($sessionId = null)
	{
		$this->handler->start ($sessionId);
	}

	/**
	 * Disconnect session handler
	 */
	public function disconnect ()
	{
		if ($this->handler)
		{
			$this->handler->stop ();
		}
	}

	/**
	 * Give the active session a new id and remove the stored session of
	 * the old one. The session data is kept. Call this whenever the
	 * privileges of the session change, for example when a user logs in.
	 * Does nothing when no session is active, or when the new session
	 * cookie could not be sent any more.
	 * @return bool True if the session has a new id.
	 */
	public function regenerate ()
	{
		if (session_status () !== PHP_SESSION_ACTIVE)
		{
			return false;
		}

		if (headers_sent () && ini_get ('session.use_cookies'))
		{
			return false;
		}

		return session_regenerate_id (true);
	}

	/**
	 * Destroy a session
	 */
	public function destroy ()
	{
		// Unset all of the session variables.
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		// Finally, destroy the session.
		session_destroy();
	}

	public function set ($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public function all ()
	{
		return $_SESSION;
	}

	public function get ($key)
	{
		if (isset ($_SESSION[$key]))
		{
			return $_SESSION[$key];
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getSessionQueryString()
	{
		return $this->handler->getSessionQueryString();
	}
}
