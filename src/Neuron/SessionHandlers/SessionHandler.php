<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 15:17
 */

namespace Neuron\SessionHandlers;

use bmgroup\Cloudwalkers\Models\Logger;
use Exception;
use Neuron\Core\Tools;

abstract class SessionHandler
{
	private $started = false;

	public final function start ($sessionId = null)
	{
		if (!$this->started)
		{
			/*
			var_dump ($_COOKIE);
			var_dump ($_REQUEST);
			*/
			
			if (isset($sessionId))
			{
				session_id ($sessionId);

				ini_set("session.use_cookies",0);
				ini_set("session.use_only_cookies",0);
				ini_set("session.use_trans_sid",0); # Forgot this one!

				Logger::getInstance()->log ("Starting session with provided id " . $sessionId, false, 'cyan');
			}
			
			else if ($defaultSession = Tools::getInput ($_COOKIE, 'PHPSESSID', 'varchar'))
			{
				Logger::getInstance()->log ("Starting session with default cookie " . $defaultSession, false, 'cyan');
				session_id ($defaultSession);
			}
			
			else 
			{
				/**
				 * @TODO check if this is proper
				 * 
				 * We're using uniqid to set the session id.
				 */
				$sid = uniqid ('session', true);
				session_id ($sid);
				Logger::getInstance()->log ("Starting brand new session with id " . $sid, false, 'cyan');
			}

			session_start ();

			$this->started = true;
		}
	}

	public final function stop ()
	{
		Logger::getInstance()->log ("Closing session with id " . session_id (), false, 'cyan');

		session_write_close ();
		$this->started = false;
	}

	/* Methods */
	abstract public function close (  );
	abstract public function destroy ( $session_id );
	abstract public function gc ( $maxlifetime );
	abstract public function open ( $save_path , $name );
	abstract public function read ( $session_id );
	abstract public function write ( $session_id , $session_data );

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