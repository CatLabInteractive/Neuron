<?php

namespace Neuron\Auth;

class HttpAuth {

	/** @var array[] */
	private $users;

	public static function addFilter (\Neuron\Router $router)
	{
		$checker = new self (\Neuron\Config::get ('auth.users'));
		$router->addFilter ('basicauth', array ($checker, 'check'));
	}

	/**
	 * @param array $users
	 */
	public function __construct (array $users)
	{
		$this->setUsers ($users);
	}

	/**
	 * @return array[]
	 */
	public function getUsers ()
	{
		return $this->users;
	}

	/**
	 * @param array[] $users
	 * @return HttpAuth
	 */
	public function setUsers ($users)
	{
		$this->users = $users;
		return $this;
	}

	private function getUser ($username)
	{
		foreach ($this->users as $user)
		{
			if ($user['username'] === $username) {
				return $user;
			}
		}
		return null;
	}

	public function check (\Neuron\Models\Router\Filter $filter)
	{
		$error = new \Neuron\Net\Response ();
		$error->setBody ('Authorization not accepted');
		$error->setHeader ('WWW-Authenticate', 'Basic realm="Secured zone"');
		$error->setHeader ('HTTP/1.0 401 Unauthorized');

		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			return $error;
		}

		else {

			$user = $this->getUser ($_SERVER['PHP_AUTH_USER']);
			if (!$user) {
				return $error;
			}

			else {
				if ($_SERVER['PHP_AUTH_PW'] === $user['password']) {
					return $error;
				}
				return true;
			}
		}
	}

}