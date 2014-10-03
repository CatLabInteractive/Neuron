<?php


namespace Neuron\Mappers;

use Neuron\DB\Database;
use Neuron\DB\Query;
use Neuron\Models\User;


class UserMapper
{
    /**
     * @param $id
     * @return User|null
     */
    public function getFromId ($id)
	{
		$db = Database::getInstance ();

		$data = $db->query
		("
			SELECT 
				*
			FROM
				users
			WHERE
				u_id = '{$db->escape ($id)}'
		");

		$data = $this->getObjectsFromData ($data);
		if (count ($data) > 0)
		{
			return $data[0];
		}
		return null;
	}

	/**
	 * @param int[] $ids
	 * @return User[]
	 */
	public function getFromIds ($ids)
	{
		if (count ($ids) == 0)
		{
			return array ();
		}

		$list = array ();
		foreach ($ids as $v)
		{
			$list[] = intval ($v);
		}
		$list = implode (',', $list);

		$db = Database::getInstance ();

		$data = $db->query
			("
			SELECT
				*
			FROM
				users
			WHERE
				u_id IN ({$list})
		");

		return $this->getObjectsFromData ($data);
	}

    /**
     * @param $email
     * @return User|null
     */
    public function getFromEmail ($email)
	{
		$db = Database::getInstance ();

		$data = $db->query
		("
			SELECT 
				*
			FROM
				users
			WHERE
				u_email = '{$db->escape ($email)}'
		");

		$data = $this->getObjectsFromData ($data);
		if (count ($data) > 0)
		{
			return $data[0];
		}
		return null;
	}

    /**
     * @param $email
     * @param $password
     * @return User|null
     */
    public function getFromLogin ($email, $password)
	{
		$db = Database::getInstance ();

		$data = $db->query 
		("
			SELECT
				*
			FROM
				users
			WHERE
				u_email = '{$db->escape ($email)}' AND 
				u_password = MD5(CONCAT('{$db->escape ($password)}', u_salt))
		");

		$data = $this->getObjectsFromData ($data);
		if (count ($data) > 0)
		{
			return $data[0];
		}
		return null;
	}

	/**
	* Return hashed password & salt.
	*/
	private function hashPassword ($password)
	{
		// To be replaced by something more ... smart.
		$salt = md5 (mt_rand (0, 1000000) . ' ' . time ());
		$password .= $salt;
		return array (md5 ($password), $salt);
	}

    /**
     * @param User $user
     * @return array
     */
    protected function prepareFields (User $user)
	{
		//  Hash the password & add some salt.
		$out = array ();

		$password = $user->getPassword ();
		if (!empty ($password))
		{
			$hashes = $this->hashPassword ($user->getPassword ());

			$out['u_password'] = $hashes[0];
			$out['u_salt'] = $hashes[1];
		}

		// Name & firstname
		$name = $user->getName ();
		$firstname = $user->getFirstname ();
		if (!empty ($name))
		{
			//$sql .= "u_name = '{$db->escape ($name)}', ";
			$out['u_name'] = $name;
		}

		if (!empty ($firstname))
		{
			//$sql .= "u_firstname = '{$db->escape ($user->getFirstname ())}', ";
			$out['u_firstname'] = $firstname;
		}

		$email = $user->getEmail ();
		if (!empty ($email))
		{
			$out['u_email'] = $email;
		}
		else
		{
			//$sql .= "u_email = NULL, ";
			$out['u_email'] = array (null, Query::PARAM_NULL, true);
		}
		
		$mobile = $user->getMobile ();
		if (!empty ($mobile))
		{
			$out['u_mobile'] = $mobile;
		}
		else
		{
			$out['u_mobile'] = array (null, Query::PARAM_NULL, true);
		}

		$out['u_isEmailValidated'] = 0;
        $out['updated_at'] = array (time (), Query::PARAM_DATE);

		return $out;
	}

    /**
     * @param User $user
     * @return User
     */
    public function create (User $user)
	{
        $data = $this->prepareFields ($user);

        $data['created_at'] = array (time (), Query::PARAM_DATE);

		$query = Query::insert ('users', $data);
		$id = $query->execute ();

		// Set ID in object
		//$user->setId ($id);

		//return $user;

		return $this->getFromId ($id);
	}

    /**
     * @param User $user
     */
    public function update (User $user)
	{
		$where = array (
			'u_id' => $user->getId ()
		);

		$query = Query::update ('users', $this->prepareFields ($user), $where);
		$query->execute ();
	}

    /**
     * @param User $user
     * @param $password
     * @return bool
     */
    public function checkPassword (User $user, $password)
	{
		$db = Database::getInstance ();

		$chk = $db->query 
		("
			SELECT
				*
			FROM
				users
			WHERE
				u_id = {$user->getId ()} AND
				u_password = MD5(CONCAT('{$db->escape ($password)}', u_salt))
		");

		return count ($chk) > 0;
	}

    /**
     * @param User $user
     * @return bool
     */
    public function hasPassword (User $user)
	{
		$db = Database::getInstance ();

		$chk = $db->query
		("
			SELECT
				*
			FROM
				users
			WHERE
				u_id = {$user->getId ()} AND
				u_password IS NOT NULL
		");

		return count ($chk) > 0;
	}

    /**
     *
     */
    public function removeExpiredPasswordResetTokens ()
	{
		$db = Database::getInstance ();

		$db->query 
		("
			DELETE FROM
				users_passwordreset
			WHERE
				upw_date < NOW() - INTERVAL 1 DAY
		");
	}

    /**
     * @param User $user
     * @param $token
     * @param $ip
     */
    public function addPasswordResetToken (User $user, $token, $ip)
	{
		$this->removeExpiredPasswordResetTokens ();

		$db = Database::getInstance ();

		$ip = inet_pton ($ip);

		$db->query 
		("
			INSERT INTO
				users_passwordreset
			SET
				u_id = {$user->getId ()},
				upw_token = '{$db->escape ($token)}',
				upw_date = NOW(),
				upw_ip = '{$db->escape ($ip)}'
		");
	}

    /**
     * @param User $user
     * @return array
     */
    public function getPasswordResetTokens (User $user)
	{
		$db = Database::getInstance ();

		$data = $db->query 
		("
			SELECT
				*,
				UNIX_TIMESTAMP(upw_date) AS datum
			FROM
				users_passwordreset
			WHERE
				u_id = {$user->getId ()} AND
				upw_date > NOW() - INTERVAL 1 DAY
		");

		$out = array ();
		foreach ($data as $v)
		{
			$out[] = array 
			(
				'code' => $v['upw_token'],
				'date' => $v['datum'],
				'ip' => inet_ntop ($v['upw_ip'])
			);
		}
		return $out;
	}

    /**
     * @param $data
     * @return User[]
     */
    public function getObjectsFromData ($data)
	{
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = $this->getObjectFromData ($v);
		}
		return $out;
	}

    /**
     * Return the name of the models that will be instanciated
     * @return string
     */
    public function getObjectClassname ()
	{
		return 'Neuron\Models\User';
	}

    /**
     * @param $data
     * @return User|null
     */
    public function getSingle ($data)
	{
		if (count ($data) > 0)
		{
			return $this->getObjectFromData ($data[0]);
		}
		return null;
	}

	/**
	 * @param $data
	 * @return User
	 */
	public function getObjectFromData ($data)
	{
		$classname = $this->getObjectClassname ();

		/** @var \Neuron\Models\User $user */
		$user = new $classname ($data['u_id']);

		if (!empty ($data['u_email']))
			$user->setEmail ($data['u_email']);

		if (!empty ($data['u_name']))
			$user->setName ($data['u_name']);

		if (!empty ($data['u_firstname']))
			$user->setFirstname ($data['u_firstname']);

		if (!empty ($data['u_admin_status']))
			$user->setAdmin ($data['u_admin_status']);

		$user->setEmailValidated ($data['u_isEmailValidated'] == 1);

		return $user;
	}
}