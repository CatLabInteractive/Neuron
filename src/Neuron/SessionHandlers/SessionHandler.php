<?php

namespace Neuron\SessionHandlers;

use Neuron\Models\Logger;
use Neuron\Core\Tools;

/**
 * Class SessionHandler
 * @package Neuron\SessionHandlers
 */
class SessionHandler
	extends \SessionHandler
{
	private $started = false;

	const SESSION_QUERY_PARAMETER = 'PSID';

	public final function start ($sessionId = null)
	{
		if (!$this->started)
		{
			$this->register ();

            if (isset($sessionId)) {
                session_id ($sessionId);

                ini_set ("session.use_cookies", 0);
                ini_set ("session.use_only_cookies", 0);
                ini_set ("session.use_trans_sid", 0); # Forgot this one!

                Logger::getInstance ()->log ("Starting session with provided id " . $sessionId, false, 'cyan');
            } elseif ($defaultSession = Tools::getInput ($_COOKIE, 'PHPSESSID', 'varchar')) {
				Logger::getInstance()->log("Starting session with default cookie " . $defaultSession, false, 'cyan');
				session_id($defaultSession);
			} elseif ($queryParamSession = Tools::getInput ($_GET, self::SESSION_QUERY_PARAMETER, 'varchar')) {
				Logger::getInstance()->log("Starting session with query parameter " . $queryParamSession, false, 'cyan');
				session_id($queryParamSession);
            } elseif (session_status() == PHP_SESSION_ACTIVE) {
                session_regenerate_id ();
                Logger::getInstance ()->log ("Starting brand new session with id " . session_id (), false, 'cyan');
            }

			session_start ();

			$this->started = true;
		}
	}

	public final function stop ()
	{
		Logger::getInstance ()->log ("Closing session with id " . session_id (), false, 'cyan');

		session_write_close ();
		$this->started = false;
	}

	/**
	 * @return string
	 */
	public function getSessionQueryString()
	{
		return self::SESSION_QUERY_PARAMETER . '=' . session_id();
	}

	/* Methods */
	public function close ()
	{
		return parent::close ();
	}

	public function destroy ($session_id)
	{
		return parent::destroy ($session_id);
	}

	public function gc ( $maxlifetime )
	{
		return parent::gc ($maxlifetime);
	}

	public function open ( $save_path , $name )
	{
		return parent::open ( $save_path, $name );
	}

	public function read ( $session_id )
	{
		return parent::read ($session_id);
	}


	public function write ( $session_id , $session_data )
	{
		return parent::write ($session_id, $session_data);
	}

	public function register ()
	{

		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc')
		);
	}
} 
