<?php


namespace Neuron\Models;

use bmgroup\Cloudwalkers\Models\Account;
use Neuron\Core\Tools;
use Neuron\MapperFactory;
use Neuron\Mappers\UserMapper;
use Neuron\Mappers\EmailMapper;
use bmgroup\Base\Utilities\TokenGenerator;
use Neuron\Core\Template;
use Neuron\URLBuilder;
use bmgroup\Base\Utilities\Email;
use bmgroup\Mailer\Models\Mailer as Mailer;


class User
{
	private $id;
	private $email;

	/*
	* Array of emails that are registered with this account.
	* (only one active at any time.)
	*/
	private $emails;

	private $password;
	private $admin;

	private $name;
	private $firstname;
	private $mobile;

	private $accounts;

	private $blnIsManager;
	private $blnIsLover;

	private $errors;
	private $feedbacks;

	private $discountshare;
	private $commissionshares;

	private $brands;
	private $emailValidated;

	public function __construct ($id)
	{
		$this->setId ($id);
	}

	public function setId ($id)
	{
		$this->id = $id;
	}

	public function getId ()
	{
		return intval ($this->id);
	}

	public function setEmail ($email)
	{
		$this->email = $email;
	}

	public function getEmail ()
	{
		return $this->email;
	}

	public function setMobile ($mobile)
	{
		$this->mobile = $mobile;
	}

	public function getMobile ()
	{
		return $this->mobile;
	}

	/**
	 * get user is admin previleges level
	 * this is not related with admin of accounts
	 */
	public function getAdminRights ()
	{
		return $this->admin;
	}

	/**
	* Only used during registration.
	*/
	public function setPassword ($password)
	{
		$this->password = $password;
	}

	/**
	* Only used during registration.
	*/
	public function getPassword ()
	{
		return $this->password;
	}

	public function setName ($name)
	{
		$this->name = $name;
	}

	public function getName ()
	{
		return $this->name;
	}

	public function setFirstname ($name)
	{
		$this->firstname = $name;
	}

	public function getFirstname ()
	{
		return $this->firstname;
	}

	public function getFullName ()
	{
		return $this->getFirstname () . ' ' . $this->getName ();
	}

	public function setAdmin ($admin_id)
	{
		$this->admin = $admin_id;
	}

	public function getDisplayName ()
	{
		return Tools::output_varchar ($this->getFullName ());
	}

	/**
	* Return a list of all accounts this user has.
	* Lazy loading, mysql query is done first time the accounts are requested.
	 * @return Account[]
	*/
	public function getAccounts ()
	{
		if (!isset ($this->accounts))
		{
			$this->accounts = MapperFactory::getAccountMapper ()->getFromUser ($this);
		}
		return $this->accounts;
	}

	public function hasAccount (Account $type)
	{
		$typename = get_class ($type);

		foreach ($this->getAccounts () as $v)
		{
			if ($v instanceof $typename)
			{
				return true;
			}
		}
		return false;
	}

	/**
	* Password. Now here is something special.
	* It is possible that no password is set (when logged in through third party)
	*/
	public function hasPassword ()
	{
		return MapperFactory::getUserMapper ()->hasPassword ($this);
	}

	public function hasEmail ()
	{
		$email = $this->getEmail ();
		return !empty ($email);
	}

	public function doesPasswordMatch ($password)
	{
		return MapperFactory::getUserMapper ()->checkPassword ($this, $password);
	}

	public function changePassword ($oldpassword, $password, $repeatpassword, $ignoreOldPassword = false)
	{
		if (!$this->hasEmail ())
		{
			$this->addError ('Please first set an email address before setting a password.');
			return false;
		}

		if (empty ($password) || strlen ($password) < 6)
		{
			$this->addError ('Your password is too short. Please pick a password of at least 6 characters.');
			return false;
		}

		if ($password != $repeatpassword)
		{
			$this->addError ('Your passwords do not match.');
			return false;
		}

		if ($this->hasPassword () && !$ignoreOldPassword && !$this->doesPasswordMatch ($oldpassword))
		{
			$this->addError ('Your old password is not correct.');
			return false;
		}

		// Aaaand we change the password.
		$this->addFeedback ('Your password was changed.');

		$this->setPassword ($password);
		MapperFactory::getUserMapper ()->update ($this);

		return true;
	}

	public function getIp ()
	{
		return isset ($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	/**
	* Initialise a reset password procedure:
	* send an email to the user.
	*/
	public function startResetPassword ()
	{
		// Create the code
		$code = TokenGenerator::getToken (10);
		MapperFactory::getUserMapper ()->addPasswordResetToken ($this, $code, $this->getIp ());

		$mail = Mailer::getInstance()
			->toUser($this)
			->setTemplate('reset_password/reset_password')
			->setAttribute('code_url', URLBuilder::getUrl ('login/lostpassword', array ('id' => $this->getId (), 'code' => $code)))
			->setAttribute('user-firstname', $this->getFirstname())
			->setAttribute('logo', URLBuilder::getURL('assets/img/logo.png'))
			->schedule();
	}

	public function hasResetToken ($strcode)
	{
		$codes = MapperFactory::getUserMapper ()->getPasswordResetTokens ($this);
		foreach ($codes as $code)
		{
			if ($code['code'] == $strcode)
			{
				return true;
			}
		}

		return false;
	}

	private function addError ($message)
	{
		$this->errors[] = $message;
	}

	public function getErrors ()
	{
		return $this->errors;
	}

	private function addFeedback ($message)
	{
		$this->feedbacks[] = $message;
	}

	public function getFeedback ()
	{
		return $this->feedbacks;
	}

	public function setEmailValidated ($validated)
	{
		$this->emailValidated = $validated;
	}

	/**
	* Return true if email is validated
	*/
	public function isEmailValidated ()
	{
		return $this->emailValidated;
	}

	public function equals ($other)
	{
		return $this->getId () === $other->getId ();
	}

	public function getData ()
	{
		return array (
			'id' => $this->getId (),
			'firstname' => $this->getFirstName (),
			'name' => $this->getName ()
		);
	}
}

/**
 * @SWG\Model(
 *  id="UserPasswordInput",
 *  @SWG\Property(name="oldpassword",type="string",required=true),
 *  @SWG\Property(name="newpassword",type="string",required=true)
 * )
 */