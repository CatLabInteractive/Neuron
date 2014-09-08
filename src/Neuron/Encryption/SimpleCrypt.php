<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 26/08/14
 * Time: 12:46
 */

namespace Neuron\Encryption;


class SimpleCrypt {

	private $password;
	
	public function __construct ($password)
	{
		$this->password = $password;
	}
	
	public function encrypt ($value)
	{
		$value .= '|||CWSALT' . mt_rand ();

		$encryptionMethod = "AES-256-CBC";
		$secretHash = md5 ($this->password);

		return openssl_encrypt($value, $encryptionMethod, $secretHash, false, substr ($secretHash, 0, 16));
	}
	
	public function decrypt ($encrypted)
	{
		$encryptionMethod = "AES-256-CBC";
		$secretHash = md5 ($this->password);

		$decrypted = openssl_decrypt ($encrypted, $encryptionMethod, $secretHash, false, substr ($secretHash, 0, 16));

		$decrypted = explode ('|||CWSALT', $decrypted);

		if (count ($decrypted) > 1)
		{
			array_pop ($decrypted);
		}

		$decrypted = implode ('|||CWSALT', $decrypted);

		return $decrypted;
	}
} 