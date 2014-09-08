<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 15:17
 */

namespace Neuron\SessionHandlers;

use Neuron\DB\Query;

class DbSessionHandler
	extends SessionHandler
{
	/** @var \Neuron\DB\Database $db */
	private $db;
	private $sessions = array ();

	/* Methods */
	public function open ( $save_path , $name )
	{
		// Force loading of query.
		$this->db = \Neuron\DB\Database::getInstance ();
		
		// Nothing to do.
		return true;
	}

	public function close (  )
	{
		// Nothing to do here either.
		return true;
	}

	public function destroy ( $session_id )
	{
		Query::delete ('sessions', array ('id' => $session_id))->execute ();
		return true;
	}

	public function gc ( $maxlifetime )
	{
		Query::delete ('sessions', array ('set_time' => array (time () - 60 * 10, Query::PARAM_NUMBER, '<')))->execute ();
		return true;
	}

	public function read ( $session_id )
	{
		if (!isset ($this->sessions[$session_id]))
		{
			$data = Query::select ('sessions', array ('data'), array ('id' => $session_id))->execute ();
			if (count ($data) > 0)
			{
				$this->sessions[$session_id] = $data[0]['data'];
			}
			else
			{
				$this->sessions[$session_id] = null;
			}
		}
		return $this->sessions[$session_id];
	}

	public function write ( $session_id , $session_data )
	{
		$this->sessions[$session_id] = $session_data;
		
		$time = time ();
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