<?php


namespace Neuron;

use Neuron\Models\User;
use Neuron\SessionHandlers\SessionHandler;

class Session
{
	private $user;
	private $application;
	private $values;
	
	/** @var SessionHandler */
	private $handler;
	
	private static $instance;
	

	public static function getInstance ()
	{
		if (empty (self::$instance))
		{
			self::$instance = new self ();
		}
		return self::$instance;
	}
	
	public static function clearInstance ()
	{
		self::$instance = null;
	}

	private function __construct ()
	{

	}

	public function getSessionId ()
	{
		return session_id ();
	}

	/**
	 * Start a session
	 */
	public function connect ($sessionId = null)
	{
		if (!isset ($this->handler))
		{
			$this->handler = new \Neuron\SessionHandlers\DbSessionHandler ();
			$this->handler->register ();
		}
		$this->handler->start ($sessionId);
		
		//$this->handler = $handler;
		
		/*
		if (isset($_GET['session_id']))
		{
			session_id($_GET['session_id']);
		}

		session_start ();
		*/
	}

	public function disconnect ()
	{
		if ($this->handler)
		{
			$this->handler->stop ();
		}
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

	public function setUser (User $user)
	{
		$this->user = $user;
	}

	public function isLogin ()
	{
		return isset ($_SESSION['user']);
	}

	private function loadUser ()
	{
		if (!isset ($this->user))
		{
			if (isset ($_SESSION['user']))
			{
				$this->setUser (MapperFactory::getUserMapper()->getFromId ($_SESSION['user']));
			}
			else
			{
				$this->user = false;
			}
		}
	}
		
	/**
	 * @return \Neuron\Models\User
	 */
	public function getUser()
	{
		$this->loadUser ();
		return $this->user;
	}

	public function login (User $user)
	{
		$_SESSION['user'] = $user->getId ();
		$this->user = $user;

		$_SESSION['user_admin_status'] = $user->getAdminRights ();

		// Events!
		EventManager::getInstance ()->trigger ('signin:login', $user);

		return $user;
	}

	public function logout ()
	{
		$_SESSION['user'] = null;
		$this->user = null;

		EventManager::getInstance ()->trigger ('signin:logout');

		session_destroy ();
	}

	public function register (User $user)
	{
		// Create the user
		$user = MapperFactory::getUserMapper()->create ($user);

		EventManager::getInstance ()->trigger ('signin:register', $user);

		// Login the user
		$this->login ($user);

		return $user;
	}

	public function isEmailUnique ($email)
	{
		$user = MapperFactory::getUserMapper()->getFromEmail ($email);
		if ($user)
		{
			return false;
		}
		return true;
	}

	public function setApplication ($application)
	{
		$this->application = $application;
	}

	public function getApplication ()
	{
		return $this->application;
	}

	public function set ($key, $value)
	{
		$this->values[$key] = $value;
	}

	public function get ($key)
	{
		if (isset ($this->values[$key]))
		{
			return $this->values[$key];
		}
		return null;
	}
}