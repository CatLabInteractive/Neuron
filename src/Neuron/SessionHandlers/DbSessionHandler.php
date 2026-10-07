<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 15:17
 */

namespace Neuron\SessionHandlers;

use Neuron\DB\Database;
use Neuron\DB\Query;

class DbSessionHandler
	extends SessionHandler
{
	/** @var Database $db */
	private $db;

	/** @var array Session data by id; null for an id without a (live) session. */
	private $sessions = array ();

	/**
	 * Has a session that was last written at $setTime outlived its lifetime?
	 * @param int|string $setTime Unix timestamp of the last write.
	 * @param int $now
	 * @param int $maxLifetime In seconds.
	 * @return bool
	 */
	public static function isExpired ($setTime, $now, $maxLifetime)
	{
		return (int) $setTime < (int) $now - (int) $maxLifetime;
	}

	/**
	 * Seconds a session may go without being written before it expires:
	 * session.gc_maxlifetime.
	 * @return int
	 */
	protected function getMaxLifetime ()
	{
		return (int) ini_get ('session.gc_maxlifetime');
	}

	/**
	 * @return int
	 */
	protected function now ()
	{
		return time ();
	}

	/* Methods */
	#[\ReturnTypeWillChange]
	public function open ( $save_path , $name )
	{
		// Force loading of query.
		$this->db = Database::getInstance ();
		
		// Nothing to do.
		return true;
	}

	#[\ReturnTypeWillChange]
	public function close (  )
	{
		// Nothing to do here either.
		return true;
	}

	#[\ReturnTypeWillChange]
	public function destroy ( $session_id )
	{
		Query::delete ('sessions', array ('id' => array ($session_id, Query::PARAM_STR)))->execute ();
		$this->sessions[$session_id] = null;

		return true;
	}

	#[\ReturnTypeWillChange]
	public function gc ( $maxlifetime )
	{
		Query::delete ('sessions', array ('set_time' => array ($this->now () - (int) $maxlifetime, Query::PARAM_NUMBER, '<')))->execute ();
		return true;
	}

	#[\ReturnTypeWillChange]
	public function read ( $session_id )
	{
		$data = $this->load ($session_id);
		return $data === null ? '' : $data;
	}

	/**
	 * The data of a stored session that has not expired, or null. A row
	 * that outlived the lifetime is removed.
	 * @param string $session_id
	 * @return string|null
	 */
	private function load ($session_id)
	{
		if (!self::isValidSessionId ($session_id))
		{
			return null;
		}

		if (!array_key_exists ($session_id, $this->sessions))
		{
			$this->sessions[$session_id] = null;

			$rows = Query::select (
				'sessions',
				array ('data', 'set_time'),
				array ('id' => array ($session_id, Query::PARAM_STR))
			)->execute ();

			if (count ($rows) > 0)
			{
				if (self::isExpired ($rows[0]['set_time'], $this->now (), $this->getMaxLifetime ()))
				{
					$this->destroy ($session_id);
				}
				else
				{
					$this->sessions[$session_id] = (string) $rows[0]['data'];
				}
			}
		}

		return $this->sessions[$session_id];
	}

	/**
	 * @param string $session_id
	 * @return bool
	 */
	protected function sessionExists ($session_id)
	{
		return $this->load ($session_id) !== null;
	}

	#[\ReturnTypeWillChange]
	public function write ( $session_id , $session_data )
	{
		$this->sessions[$session_id] = $session_data;
		
		$time = $this->now ();
		$this->db->query 
		("
			REPLACE 
				sessions 
			SET 
				id = '{$this->db->escape ($session_id)}', 
				set_time = '{$time}', 
				data = '{$this->db->escape ($session_data)}' 
		");

		/*
		$data = array ();
		$data['id'] = $session_id;
		$data['set_time'] = time ();
		$data['data'] = $session_data;

		Query::replace ('sessions', $data)->execute ();
		*/

		return true;
	}
} 
