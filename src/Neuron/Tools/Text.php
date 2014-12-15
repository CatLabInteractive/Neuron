<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 21:36
 */

namespace Neuron\Tools;


class Text {

	/**
	 * @return Text
	 */
	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}
		return $in;
	}

	/** @var string $domain */
	private $domain;

	/**
	 * @param string $domain
	 * @param string $path
	 */
	public function addPath ($domain, $path)
	{
		\bindtextdomain ($domain, $path);
	}

	/**
	 * @param string $domain
	 */
	public function setDomain ($domain)
	{
		$this->domain = $domain;
	}

	/**
	 * @param string $message1
	 * @param string|null $message2
	 * @param int|null $n
	 * @return string
	 */
	public function getText ($message1, $message2 = null, $n = null)
	{
		if (!isset ($message2)) {
			return dgettext ($this->domain, $message1);
		}
		else {
			return dngettext ($this->domain, $message1, $message2, $n);
		}
	}

	/**
	 * Little helper method.
	 * @param string $message1
	 * @param string|null $message2
	 * @param string|null $n
	 * @return string
	 */
	public static function get ($message1, $message2 = null, $n = null)
	{
		$in = self::getInstance ();
		return $in->getText ($message1, $message2, $n);
	}
}