<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 15:17
 */

namespace Neuron\SessionHandlers;

use Neuron\Models\Logger;
use Neuron\Core\Tools;

class SessionHandler
	extends \SessionHandler
{
	private $started = false;

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
			} else if ($defaultSession = Tools::getInput ($_COOKIE, 'PHPSESSID', 'varchar')) {
				Logger::getInstance ()->log ("Starting session with default cookie " . $defaultSession, false, 'cyan');
				session_id ($defaultSession);
			} else {
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