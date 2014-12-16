<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 15:17
 */

namespace Neuron\SessionHandlers;

use Neuron\Exceptions\NotImplemented;

class DefaultSessionHandler
	extends SessionHandler
{

	public function close ()
	{
		throw new NotImplemented ("Don't register this handler.");
	}

	public function destroy ($session_id)
	{
		throw new NotImplemented ("Don't register this handler.");
	}

	public function gc ($maxlifetime)
	{
		throw new NotImplemented ("Don't register this handler.");
	}

	public function open ($save_path, $name)
	{
		throw new NotImplemented ("Don't register this handler.");
	}

	public function read ($session_id)
	{
		throw new NotImplemented ("Don't register this handler.");
	}

	public function write ($session_id, $session_data)
	{
		throw new NotImplemented ("Don't register this handler.");
	}

	public function register ()
	{
		// Do nothing.
	}
}