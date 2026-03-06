<?php

namespace Neuron\Tests;

use PHPUnit\Framework\TestCase;
use Neuron\Encryption\SimpleCrypt;

class SimpleCryptTest extends TestCase
{
	public function testEncryptDecrypt ()
	{
		$crypt = new SimpleCrypt ('test_password');
		$original = 'Hello World!';

		$encrypted = $crypt->encrypt ($original);
		$this->assertNotEquals ($original, $encrypted);

		$decrypted = $crypt->decrypt ($encrypted);
		$this->assertEquals ($original, $decrypted);
	}

	public function testDifferentPasswordsFail ()
	{
		$crypt1 = new SimpleCrypt ('password1');
		$crypt2 = new SimpleCrypt ('password2');

		$encrypted = $crypt1->encrypt ('secret');
		$decrypted = $crypt2->decrypt ($encrypted);

		$this->assertNotEquals ('secret', $decrypted);
	}

	public function testEncryptProducesDifferentOutput ()
	{
		$crypt = new SimpleCrypt ('password');

		$encrypted1 = $crypt->encrypt ('same text');
		$encrypted2 = $crypt->encrypt ('same text');

		// Due to random salt, encrypted values should differ
		$this->assertNotEquals ($encrypted1, $encrypted2);
	}

	public function testEncryptDecryptEmptyString ()
	{
		$crypt = new SimpleCrypt ('password');
		$encrypted = $crypt->encrypt ('');
		$decrypted = $crypt->decrypt ($encrypted);
		$this->assertEquals ('', $decrypted);
	}

	public function testEncryptDecryptSpecialCharacters ()
	{
		$crypt = new SimpleCrypt ('password');
		$original = "Special chars: !@#\$%^&*()_+-=[]{}|;':\",./<>?";

		$encrypted = $crypt->encrypt ($original);
		$decrypted = $crypt->decrypt ($encrypted);
		$this->assertEquals ($original, $decrypted);
	}

	public function testEncryptDecryptUTF8 ()
	{
		$crypt = new SimpleCrypt ('password');
		$original = 'Héllo Wörld 日本語';

		$encrypted = $crypt->encrypt ($original);
		$decrypted = $crypt->decrypt ($encrypted);
		$this->assertEquals ($original, $decrypted);
	}

	public function testEncryptDecryptWithSaltMarkerInContent ()
	{
		$crypt = new SimpleCrypt ('password');
		$original = 'Text with |||CWSALT inside it';

		$encrypted = $crypt->encrypt ($original);
		$decrypted = $crypt->decrypt ($encrypted);
		$this->assertEquals ($original, $decrypted);
	}
}
