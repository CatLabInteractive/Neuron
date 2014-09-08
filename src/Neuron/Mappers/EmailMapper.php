<?php


namespace Neuron\Mappers;



class EmailMapper
{
	/*
	public static function create (BMGroup_Models_Email $email)
	{
		$db = Neuron\DB\Database::getInstance ();

		$confirmed = $email->isConfirmed () ? 1 : 0;

		$id = $db->query 
		("
			INSERT INTO
				users_email
			SET
				u_id = {$email->getUser ()->getId ()},
				ue_email = '{$db->escape ($email->getEmail ())}',
				ue_code = '{$db->escape ($email->getCode ())}',
				ue_confirmed = {$confirmed}
		");

		$email->setId ($id);

		return $email;
	}

	public static function update (BMGroup_Models_Email $email)
	{
		$db = Neuron\DB\Database::getInstance ();

		$confirmed = $email->isConfirmed () ? 1 : 0;

		$db->query 
		("
			UPDATE
				users_email
			SET
				ue_confirmed = {$confirmed}
			WHERE
				ue_id = {$email->getId ()}
		");
	}

	public static function getEmails (BMGroup_Models_User $user)
	{
		$db = Neuron\DB\Database::getInstance ();

		$data = $db->query 
		("
			SELECT
				*
			FROM
				users_email
			WHERE
				u_id = {$user->getId ()}
		");

		return self::getObjectsFromData ($data);
	}

	public static function getFromCode ($id, $code)
	{
		$db = Neuron\DB\Database::getInstance ();

		$id = intval ($id);

		$data = $db->query 
		("
			SELECT
				*
			FROM
				users_email
			WHERE
				ue_id = '{$id}' AND
				ue_code = '{$db->escape ($code)}'
		");

		return self::getSingle ($data);
	}

	public static function getPendingEmail (BMGroup_Models_User $user)
	{
		$db = Neuron\DB\Database::getInstance ();

		$data = $db->query 
		("
			SELECT
				*
			FROM
				users_email
			WHERE
				u_id = {$user->getId ()} AND
				ue_confirmed = 0
		");

		return self::getObjectsFromData ($data);
	}

	public static function removePendingEmails (BMGroup_Models_User $user)
	{
		$db = Neuron\DB\Database::getInstance ();

		$data = $db->query 
		("
			DELETE FROM
				users_email
			WHERE
				u_id = {$user->getId ()} AND
				ue_confirmed = 0
		");
	}

	private static function getSingle ($data)
	{
		if (count ($data) > 0)
		{
			return self::getObjectFromData ($data[0]);
		}
		return null;
	}

	private static function getObjectsFromData ($data)
	{
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = self::getObjectFromData ($v);
		}
		return $out;
	}

	private static function getObjectFromData ($v)
	{
		$tmp = new BMGroup_Models_Email ($v['ue_id']);

		$tmp->setUser (BMGroup_Mappers_UserMapper::getFromId ($v['u_id']));
		$tmp->setEmail ($v['ue_email']);
		$tmp->setCode ($v['ue_code']);
		$tmp->setConfirmed ($v['ue_confirmed'] == 1);

		return $tmp;
	}
	*/
}